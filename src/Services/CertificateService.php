<?php
declare(strict_types=1);

/**
 * CertificateService — genera el título PDF del alumno.
 *
 * Fuente: fuentes TTF incluidas en el repo (public/assets/fonts/).
 * Renderizado con imagettftext → anti-aliased, igual que la preview canvas.
 * La salida siempre es un PDF (usando FPDF vendorizado).
 *
 * NOTA: No se usan rutas del sistema (/usr/share/fonts/…) porque el hosting
 * tiene open_basedir restringido a /var/www/vhosts/… y /tmp/.
 */

require_once BASE_PATH . '/src/Helpers/fpdf.php';

class CertificateService
{
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

        // Siempre generar PDF
        $this->outputPdf($img, $fullName);
        imagedestroy($img);
    }

    // ── Resolución de fuente ────────────────────────────────────────

    /**
     * Devuelve la ruta a un TTF bold válido incluido en el propio repo.
     * Solo rutas dentro de BASE_PATH para respetar open_basedir del hosting.
     */
    private function resolveFont(): ?string
    {
        $candidates = [
            BASE_PATH . '/public/assets/fonts/Inter-Bold.ttf',
            BASE_PATH . '/public/assets/fonts/DejaVuSans-Bold.ttf',
            BASE_PATH . '/public/assets/fonts/LiberationSans-Bold.ttf',
            BASE_PATH . '/public/assets/fonts/NotoSans-Bold.ttf',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path) && filesize($path) > 10_000 && $this->isTtfValid($path)) {
                return $path;
            }
        }

        return null;
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
        // 0x00010000 = TTF, 0x4F54544F = OTF ('OTTO'), 0x74727565 = 'true' (Mac TTF)
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
