<?php
declare(strict_types=1);

/**
 * CertificateService — genera el título PDF del alumno.
 *
 * Fuente (en orden de prioridad):
 *   1. public/assets/fonts/ del repo (si se sube un TTF válido >10 KB)
 *   2. /tmp/rsgrup_fonts/Inter-Bold.ttf (caché, se descarga la primera vez)
 *      Mirrors en orden: jsDelivr → GitHub raw → fonts.gstatic (Google)
 *
 * /tmp está dentro de open_basedir en Plesk.
 * fpdf.php se carga en lazy load para no romper todos los requests.
 */

class CertificateService
{
    private const FONT_NAME      = 'Inter-Bold.ttf';
    private const FONT_CACHE_DIR = '/tmp/rsgrup_fonts';

    private const TTF_MIRRORS = [
        'https://cdn.jsdelivr.net/gh/rsms/inter@v4.0/src/static/Inter-Bold.ttf',
        'https://raw.githubusercontent.com/rsms/inter/v4.0/src/static/Inter-Bold.ttf',
        'https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuFuYAZNhiJ-Ek-_EeA.ttf',
    ];

    // ── Punto de entrada único (un alumno) ──────────────────────────

    public function generate(array $user): void
    {
        $img = $this->buildImage($user);
        if ($img === null) return;
        $fullName = trim(($user['name'] ?? '') . ' ' . ($user['surnames'] ?? ''));
        $this->outputPdf($img, $fullName);
        imagedestroy($img);
    }

    // ── Punto de entrada masivo ─────────────────────────────────────

    /**
     * generateBulk — devuelve el contenido binario de un PDF
     * con una página por alumno (sin enviar headers).
     *
     * @param  array  $rows  Filas con keys: name, surnames, delivery_title
     * @return string        Contenido PDF binario
     */
    public static function generateBulk(array $rows): string
    {
        $self = new self();
        $self->loadFpdf();

        $bgPath   = $self->resolveBgPath($self->getSetting('cert_bg_path', '/public/uploads/certificates/background.png'));
        $nameX    = (int)$self->getSetting('cert_name_x',        '400');
        $nameY    = (int)$self->getSetting('cert_name_y',        '300');
        $fontSize = (int)$self->getSetting('cert_name_fontsize', '36');
        $color    = ltrim($self->getSetting('cert_name_color',   '#000000'), '#');
        $fontPath = $self->resolveFont();

        $pdf = new \FPDF('L', 'mm', 'A4');

        foreach ($rows as $row) {
            $fullName = trim(($row['name'] ?? '') . ' ' . ($row['surnames'] ?? ''));

            if (!file_exists($bgPath)) {
                $img = imagecreatetruecolor(2970, 2100);
                imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
            } else {
                $ext = strtolower(pathinfo($bgPath, PATHINFO_EXTENSION));
                $img = match ($ext) {
                    'jpg', 'jpeg' => imagecreatefromjpeg($bgPath),
                    'png'         => imagecreatefrompng($bgPath),
                    default       => false,
                };
                if (!$img) continue;
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

            if ($fontPath !== null) {
                imagettftext($img, $fontSize, 0, $nameX, $nameY, $textColor, $fontPath, $fullName);
            } else {
                $self->drawTextFallback($img, $fullName, $nameX, $nameY, $fontSize, $textColor);
            }

            $tmpImg = sys_get_temp_dir() . '/cert_bulk_' . uniqid() . '.png';
            imagepng($img, $tmpImg);
            imagedestroy($img);

            $pdf->AddPage();
            $pdf->Image($tmpImg, 0, 0, 297, 210);
            @unlink($tmpImg);
        }

        // S = devuelve el PDF como string
        return $pdf->Output('S');
    }

    // ── Construir imagen para un alumno ─────────────────────────────

    private function buildImage(array $user): ?\GdImage
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
            return null;
        }

        $ext = strtolower(pathinfo($bgPath, PATHINFO_EXTENSION));
        $img = match ($ext) {
            'jpg', 'jpeg' => imagecreatefromjpeg($bgPath),
            'png'         => imagecreatefrompng($bgPath),
            default       => false,
        };
        if (!$img) {
            echo 'Error al cargar la imagen de fondo.';
            return null;
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
            imagettftext($img, $fontSize, 0, $nameX, $nameY, $textColor, $fontPath, $fullName);
        } else {
            $this->drawTextFallback($img, $fullName, $nameX, $nameY, $fontSize, $textColor);
        }

        return $img;
    }

    // ── Resolución de fuente ────────────────────────────────────────

    private function resolveFont(): ?string
    {
        // 1º: fuentes del repo (si algún día se sube un TTF real)
        $repoCandidates = [
            BASE_PATH . '/public/assets/fonts/Inter-Bold.ttf',
            BASE_PATH . '/public/assets/fonts/NotoSans-Bold.ttf',
            BASE_PATH . '/public/assets/fonts/LiberationSans-Bold.ttf',
            BASE_PATH . '/public/assets/fonts/DejaVuSans-Bold.ttf',
        ];
        foreach ($repoCandidates as $path) {
            if (file_exists($path) && filesize($path) > 10_000 && $this->isTtfValid($path)) {
                return $path;
            }
        }

        // 2º: caché en /tmp (se descarga automáticamente)
        return $this->getCachedFont();
    }

    private function getCachedFont(): ?string
    {
        $cacheDir  = self::FONT_CACHE_DIR;
        $cachePath = $cacheDir . '/' . self::FONT_NAME;

        // Limpiar caché anterior con nombre distinto
        $oldCache = $cacheDir . '/DejaVuSans-Bold.ttf';
        if (file_exists($oldCache)) @unlink($oldCache);

        // Usar caché si ya existe y es válida
        if (file_exists($cachePath) && filesize($cachePath) > 10_000 && $this->isTtfValid($cachePath)) {
            return $cachePath;
        }

        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

        $context = stream_context_create([
            'http' => [
                'timeout'         => 15,
                'follow_location' => true,
                'max_redirects'   => 5,
                'user_agent'      => 'RSGrup/1.0 (+https://rsgrup.es)',
            ],
            'ssl' => ['verify_peer' => false],
        ]);

        foreach (self::TTF_MIRRORS as $url) {
            $data = @file_get_contents($url, false, $context);
            if ($data === false || strlen($data) < 10_000) {
                error_log('[RSGrup] Mirror fallido: ' . $url);
                continue;
            }
            // Verificar firma TTF/OTF
            $sfVersion = unpack('N', substr($data, 0, 4))[1];
            if (!in_array($sfVersion, [0x00010000, 0x4F54544F, 0x74727565], true)) {
                error_log('[RSGrup] No es TTF válido: ' . $url);
                continue;
            }
            if (file_put_contents($cachePath, $data) !== false) {
                return $cachePath;
            }
        }

        return null;
    }

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

    // ── Fallback bitmap (si todo falla, texto GD básico) ────────────

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
        imagecopyresampled(
            $img, $tmp,
            $x, $y - ($tmpH * $scale), 0, 0,
            $tmpW * $scale, $tmpH * $scale, $tmpW, $tmpH
        );
        imagedestroy($tmp);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    private function loadFpdf(): void
    {
        if (!class_exists('FPDF')) {
            $fpdfPath = BASE_PATH . '/src/Helpers/fpdf.php';
            if (function_exists('opcache_invalidate')) opcache_invalidate($fpdfPath, true);
            require_once $fpdfPath;
        }
    }

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
        $this->loadFpdf();
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
        $row = Database::fetch('SELECT `value` FROM rsgrup_settings WHERE `key`=?', [$key]);
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
