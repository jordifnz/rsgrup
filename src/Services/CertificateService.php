<?php
declare(strict_types=1);

class CertificateService
{
    public function generate(array $user): void
    {
        $bgPath   = BASE_PATH . $this->getSetting('cert_bg_path', '/public/uploads/certificates/background.png');
        $nameX    = (int)$this->getSetting('cert_name_x', '480');
        $nameY    = (int)$this->getSetting('cert_name_y', '300');
        $fontSize = (int)$this->getSetting('cert_name_fontsize', '40');
        $color    = $this->getSetting('cert_name_color', '000000');
        $fullName = $user['name'] . ' ' . $user['surnames'];

        if (!file_exists($bgPath)) {
            http_response_code(404);
            echo 'Fondo del título no configurado.';
            return;
        }

        // Load image
        $ext = strtolower(pathinfo($bgPath, PATHINFO_EXTENSION));
        $img = match($ext) {
            'jpg','jpeg' => imagecreatefromjpeg($bgPath),
            'png'        => imagecreatefrompng($bgPath),
            default      => false,
        };
        if (!$img) { echo 'Error al cargar imagen de fondo.'; return; }

        // Parse color
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $textColor = imagecolorallocate($img, $r, $g, $b);

        // Font (use bundled or system)
        $fontPath = BASE_PATH . '/public/assets/fonts/DejaVuSans-Bold.ttf';
        if (!file_exists($fontPath)) {
            $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        }

        if (file_exists($fontPath)) {
            imagettftext($img, $fontSize, 0, $nameX, $nameY, $textColor, $fontPath, $fullName);
        } else {
            imagestring($img, 5, $nameX, $nameY, $fullName, $textColor);
        }

        // Output as PDF (use FPDF if available)
        if (class_exists('FPDF')) {
            $this->outputPdf($img, $fullName);
        } else {
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="titulo_' . Sanitize::slug($fullName) . '.png"');
            imagepng($img);
        }
        imagedestroy($img);
    }

    private function outputPdf($img, string $fullName): void
    {
        $tmpImg = sys_get_temp_dir() . '/cert_' . uniqid() . '.png';
        imagepng($img, $tmpImg);

        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->Image($tmpImg, 0, 0, 297, 210);
        $pdf->Output('D', 'titulo_' . Sanitize::slug($fullName) . '.pdf');
        unlink($tmpImg);
    }

    private function getSetting(string $key, string $default = ''): string
    {
        $row = Database::fetch('SELECT value FROM rsgrup_settings WHERE `key`=?', [$key]);
        return $row ? $row['value'] : $default;
    }
}
