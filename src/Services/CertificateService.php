<?php
declare(strict_types=1);

/**
 * CertificateService — genera el título PDF del alumno.
 *
 * Fuente: busca un TTF bold real en este orden:
 *   1. public/assets/fonts/ del propio repo (si se sube uno válido)
 *   2. /tmp/rsgrup_fonts/ (caché automática — descargado la primera vez)
 *
 * NOTA: No se usan rutas del sistema (/usr/share/fonts/…) porque Plesk
 * tiene open_basedir restringido a /var/www/vhosts/… y /tmp/.
 *
 * NOTA 2: El require_once de fpdf.php está dentro del método outputPdf()
 * (lazy load) para que un error de parse en fpdf.php no rompa TODOS los
 * requests de la aplicación, solo los que generan un título PDF.
 */

class CertificateService
{
    /** URL pública del TTF que se descarga automáticamente la primera vez */
    private const FONT_URL  = 'https://github.com/dejavu-fonts/dejavu-fonts/raw/master/ttf/DejaVuSans-Bold.ttf';
    private const FONT_NAME = 'DejaVuSans-Bold.ttf';
    private const FONT_CACHE_DIR = '/tmp/rsgrup_fonts';

    // ── Punto de entrada ────────────────────────────────────────────

    public function generate(array $user): void
    {
        $rawPath  = $this->getSetting('cert_bg_path', '/public/uploads/certificates/background.png');
        $bgPath   = $this->resolveBgPath($rawPath);
        $nameX    = (int)$this->getSetting('cert_name_x',        '400');
        $nameY    = (int)$this->getSetting('cert_name_y',        '300');
        $fontSize = (int)$this->getSetting('cert_name_fontsize', '36');
        $color    = ltrim($this->getSetting('cert_name_color',   '#000000'), '#');
        $fullName = trim(($user['name'] ?? '') . ' ' . ($user['surnames'] ?? ''));

        if (!file_exists($bgPath)) {
            http_response_code(404);
            echo 'Fondo del título no configurado. Sube un PNG en Ajustes > Título de alumnos.';
            return;
        }

        $ext = strtolower(pathinfo($bgPath, PATHINFO_EXTENSION));
        $img = match ($ext) {
            'jpg', 'jpeg' => imagecreatefromjpeg($bgPath),
            'png'         => imagecreatefrompng($bgPath),
            default       => false,
        };
        if (!$img) {
            echo 'Error al cargar la imagen de fondo.';
            return;
        }

        imagealphablending($img, true);
        imagesavealpha($img, true);

        $hex       = str_pad($color, 6, '0');
        $textColor = imagecolorallocate(
            $img,
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        );

        $fontPath = $this->resolveFont();

        if ($fontPath !== null) {
            // Anti-aliased con FreeType
            imagettftext($img, $fontSize, 0, $nameX, $nameY, $textColor, $fontPath, $fullName);
        } else {
            // Fallback último recurso (fuente bitmap de GD)
            $this->drawTextFallback($img, $fullName, $nameX, $nameY, $fontSize, $textColor);
        }

        $this->outputPdf($img, $fullName);
        imagedestroy($img);
    }

    // ── Resolución de fuente ────────────────────────────────────────

    /**
     * 1º busca TTFs válidos en el repo (public/assets/fonts/)
     * 2º usa la caché en /tmp/rsgrup_fonts/ (descargando si no existe)
     * Solo accede a rutas permitidas por open_basedir.
     */
    private function resolveFont(): ?string
    {
        // 1. Fuentes del repo
        $repoCandidates = [
            BASE_PATH . '/public/assets/fonts/Inter-Bold.ttf',
            BASE_PATH . '/public/assets/fonts/DejaVuSans-Bold.ttf',
            BASE_PATH . '/public/assets/fonts/LiberationSans-Bold.ttf',
            BASE_PATH . '/public/assets/fonts/NotoSans-Bold.ttf',
        ];
        foreach ($repoCandidates as $path) {
            if (file_exists($path) && filesize($path) > 10_000 && $this->isTtfValid($path)) {
                return $path;
            }
        }

        // 2. Caché en /tmp (descarga automática si no existe)
        return $this->getCachedFont();
    }

    /**
     * Devuelve la ruta al TTF en /tmp/rsgrup_fonts/, descargándolo si es necesario.
     * /tmp está dentro de open_basedir en Plesk.
     */
    private function getCachedFont(): ?string
    {
        $cacheDir  = self::FONT_CACHE_DIR;
        $cachePath = $cacheDir . '/' . self::FONT_NAME;

        // Ya existe y es válido
        if (file_exists($cachePath) && filesize($cachePath) > 10_000 && $this->isTtfValid($cachePath)) {
            return $cachePath;
        }

        // Crear directorio de caché
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        // Descargar con file_get_contents (más portable que curl)
        $context = stream_context_create([
            'http' => [
                'timeout'         => 10,
                'follow_location' => true,
                'user_agent'      => 'RSGrup/1.0',
            ],
            'ssl'  => ['verify_peer' => false],
        ]);

        $data = @file_get_contents(self::FONT_URL, false, $context);
        if ($data === false || strlen($data) < 10_000) {
            error_log('[RSGrup] No se pudo descargar la fuente desde ' . self::FONT_URL);
            return null;
        }

        if (file_put_contents($cachePath, $data) === false) {
            error_log('[RSGrup] No se pudo escribir la fuente en ' . $cachePath);
            return null;
        }

        if (!$this->isTtfValid($cachePath)) {
            @unlink($cachePath);
            return null;
        }

        return $cachePath;
    }

    /**
     * Comprueba que el archivo es un TTF/OTF real leyendo su magic number.
     */
    private function isTtfValid(string $path): bool
    {
        $fh = @fopen($path, 'rb');
        if (!$fh) return false;
        $header = fread($fh, 4);
        fclose($fh);
        if (strlen($header) < 4) return false;
        $sfVersion = unpack('N', $header)[1];
        return in_array($sfVersion, [0x00010000, 0x4F54544F, 0x74727565], true);
    }

    // ── Fallback bitmap (último recurso) ────────────────────────────

    private function drawTextFallback(
        \GdImage $img, string $text, int $x, int $y, int $fontSize, int $textColor
    ): void {
        $glyphH = 15;
        $scale  = max(1, (int)round($fontSize / $glyphH));
        $tmpW   = strlen($text) * 9 + 4;
        $tmpH   = $glyphH + 4;
        $tmp    = imagecreatetruecolor($tmpW, $tmpH);
        $trans  = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
        imagefill($tmp, 0, 0, $trans);
        imagesavealpha($tmp, true);
        $r = ($textColor >> 16) & 0xFF;
        $g = ($textColor >> 8)  & 0xFF;
        $b = $textColor & 0xFF;
        imagestring($tmp, 5, 2, 2, $text, imagecolorallocate($tmp, $r, $g, $b));
        imagecopyresampled($img, $tmp, $x, $y - ($tmpH * $scale), 0, 0,
            $tmpW * $scale, $tmpH * $scale, $tmpW, $tmpH);
        imagedestroy($tmp);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    private function resolveBgPath(string $path): string
    {
        if (str_starts_with($path, BASE_PATH)) return $path;
        $full = BASE_PATH . '/' . ltrim($path, '/');
        if (file_exists($full)) return $full;
        if (!str_starts_with(ltrim($path, '/'), 'public/')) {
            $withPublic = BASE_PATH . '/public/' . ltrim($path, '/');
            if (file_exists($withPublic)) return $withPublic;
        }
        return $full;
    }

    private function outputPdf(\GdImage $img, string $fullName): void
    {
        // Lazy load: fpdf.php solo se carga cuando se genera un título PDF.
        // Esto evita que un error de parse en fpdf.php rompa todos los requests.
        if (!class_exists('FPDF')) {
            // Invalidar OPcache para este archivo antes de incluirlo
            $fpdfPath = BASE_PATH . '/src/Helpers/fpdf.php';
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($fpdfPath, true);
            }
            require_once $fpdfPath;
        }

        $tmpImg = sys_get_temp_dir() . '/cert_' . uniqid() . '.png';
        imagepng($img, $tmpImg);
        $pdf = new \FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->Image($tmpImg, 0, 0, 297, 210);
        $pdf->Output('D', 'titulo_' . $this->slug($fullName) . '.pdf');
        @unlink($tmpImg);
    }

    private function getSetting(string $key, string $default = ''): string
    {
        $row = Database::fetch('SELECT value FROM rsgrup_settings WHERE `key`=?', [$key]);
        return $row ? (string)$row['value'] : $default;
    }

    private function slug(string $text): string
    {
        if (class_exists('Sanitize') && method_exists('Sanitize', 'slug')) {
            return Sanitize::slug($text);
        }
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        return trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $text)), '_') ?: 'titulo';
    }
}
