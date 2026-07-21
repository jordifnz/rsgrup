<?php
declare(strict_types=1);

class CertificateService
{
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
        $r   = hexdec(substr($hex, 0, 2));
        $g   = hexdec(substr($hex, 2, 2));
        $b   = hexdec(substr($hex, 4, 2));
        $textColor = imagecolorallocate($img, $r, $g, $b);

        $fontPath = $this->findFont();

        if ($fontPath !== null) {
            imagettftext($img, $fontSize, 0, $nameX, $nameY, $textColor, $fontPath, $fullName);
        } else {
            // Sin TTF: escalar texto bitmap
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

    private function findFont(): ?string
    {
        $candidates = [
            // Fuente empaquetada en el repo (primera prioridad)
            BASE_PATH . '/public/assets/fonts/NotoSans-Bold.ttf',
            BASE_PATH . '/public/assets/fonts/LiberationSans-Bold.ttf',
            // Debian/Ubuntu
            '/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
            // CentOS/RHEL
            '/usr/share/fonts/noto/NotoSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/liberation/LiberationSans-Bold.ttf',
            // macOS
            '/System/Library/Fonts/Helvetica.ttc',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Último recurso: glob
        foreach (['/usr/share/fonts/truetype/*Bold*.ttf', '/usr/share/fonts/**/*Bold*.ttf'] as $p) {
            $found = glob($p);
            if (!empty($found)) return $found[0];
        }

        return null;
    }

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
        $c = imagecolorallocate($tmp, $r, $g, $b);
        imagestring($tmp, 5, 2, 2, $text, $c);
        $dstW = $tmpW * $scale;
        $dstH = $tmpH * $scale;
        imagecopyresampled($img, $tmp, $x, $y - $dstH, 0, 0, $dstW, $dstH, $tmpW, $tmpH);
        imagedestroy($tmp);
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
