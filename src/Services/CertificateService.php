<?php
declare(strict_types=1);

/**
 * CertificateService — genera el título PNG/PDF del alumno.
 *
 * Orden de resolución de fuente:
 *  1. public/assets/fonts/NotoSans-Bold.ttf  (empaquetada o descargada)
 *  2. Rutas del sistema (Debian, Ubuntu, CentOS…)
 *  3. glob de cualquier *Bold*.ttf en el sistema
 *  4. Fallback bitmap escalado si no hay ninguna TTF
 */
class CertificateService
{
    /** URL de descarga de NotoSans Bold (Google Fonts mirror en jsdelivr) */
    private const FONT_CDN = 'https://cdn.jsdelivr.net/gh/googlefonts/noto-fonts@main/hinted/ttf/NotoSans/NotoSans-Bold.ttf';

    /** Ruta local donde se guarda/busca primero la fuente del repo */
    private function repoFontPath(): string
    {
        return BASE_PATH . '/public/assets/fonts/NotoSans-Bold.ttf';
    }

    // ── Punto de entrada ──────────────────────────────────────────────────────

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

        $hex = str_pad($color, 6, '0');
        $textColor = imagecolorallocate(
            $img,
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        );

        $fontPath = $this->findFont();

        if ($fontPath !== null) {
            // Anti-alias automático con imagettftext
            imagettftext($img, $fontSize, 0, $nameX, $nameY, $textColor, $fontPath, $fullName);
        } else {
            $this->drawTextFallback($img, $fullName, $nameX, $nameY, $fontSize, $textColor);
        }

        if (class_exists('FPDF')) {
            $this->outputPdf($img, $fullName);
        } else {
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="titulo_' . $this->slug($fullName) . '.png"');
            imagepng($img);
        }
        imagedestroy($img);
    }

    // ── Resolución de fuente ──────────────────────────────────────────────────

    /**
     * Devuelve la ruta a un TTF bold usable, o null si no hay ninguno.
     * Si la fuente del repo no existe en disco la intenta descargar una vez.
     */
    private function findFont(): ?string
    {
        $repoFont = $this->repoFontPath();

        // 1. Fuente empaquetada en el repo (o descargada previamente)
        if (file_exists($repoFont) && filesize($repoFont) > 50_000) {
            return $repoFont;
        }

        // 2. Intentar descargar desde CDN y cachear
        if ($this->downloadFont($repoFont)) {
            return $repoFont;
        }

        // 3. Fuentes del sistema
        $system = [
            '/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
            '/usr/share/fonts/noto/NotoSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/liberation/LiberationSans-Bold.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
        ];
        foreach ($system as $path) {
            if (file_exists($path)) return $path;
        }

        // 4. Glob: cualquier Bold del sistema
        foreach (['/usr/share/fonts/truetype/*Bold*.ttf', '/usr/share/fonts/**/*Bold*.ttf'] as $pat) {
            $found = glob($pat);
            if (!empty($found)) return $found[0];
        }

        return null;
    }

    /**
     * Descarga NotoSans-Bold.ttf desde CDN y la guarda en public/assets/fonts/.
     * Silencia errores (si no hay internet o el directorio no es escribible, sigue).
     */
    private function downloadFont(string $dest): bool
    {
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (!is_writable($dir)) {
            return false;
        }

        // Evitar re-descarga si ya existe pero estaba incompleta
        $tmp = $dest . '.tmp';

        $context = stream_context_create([
            'http' => [
                'timeout'         => 10,
                'follow_location' => 1,
                'user_agent'      => 'rsgrup-certificate/1.0',
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $data = @file_get_contents(self::FONT_CDN, false, $context);
        if ($data === false || strlen($data) < 50_000) {
            return false;
        }

        // Verificar cabecera TTF (sfVersion = 0x00010000)
        if (strlen($data) >= 4 && unpack('N', substr($data, 0, 4))[1] !== 0x00010000) {
            return false;
        }

        if (@file_put_contents($tmp, $data) === false) {
            return false;
        }

        return @rename($tmp, $dest);
    }

    // ── Fallback sin TTF ──────────────────────────────────────────────────────

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
        $b = $textColor         & 0xFF;
        imagestring($tmp, 5, 2, 2, $text, imagecolorallocate($tmp, $r, $g, $b));
        imagecopyresampled($img, $tmp, $x, $y - ($tmpH * $scale), 0, 0,
            $tmpW * $scale, $tmpH * $scale, $tmpW, $tmpH);
        imagedestroy($tmp);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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
        $tmpImg = sys_get_temp_dir() . '/cert_' . uniqid() . '.png';
        imagepng($img, $tmpImg);
        $pdf = new \FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->Image($tmpImg, 0, 0, 297, 210);
        $pdf->Output('D', 'titulo_' . $this->slug($fullName) . '.pdf');
        unlink($tmpImg);
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
