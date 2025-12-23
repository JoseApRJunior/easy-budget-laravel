<?php
declare(strict_types=1);

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\Http;

class QrCodeService
{
    public function generateDataUri(string $data, int $size = 180): string
    {
        if (class_exists('Mpdf\\QrCode\\QrCode')) {
            $qrCode = new \Mpdf\QrCode\QrCode($data);
            $output = new \Mpdf\QrCode\Output\Png();
            $png = $output->output($qrCode, $size, [255, 255, 255], [0, 0, 0]);
            $base64 = base64_encode($png);
            return 'data:image/png;base64,' . $base64;
        }

        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
        $response = Http::timeout(10)->get($url);
        if ($response->successful()) {
            $base64 = base64_encode($response->body());
            return 'data:image/png;base64,' . $base64;
        }

        return '';
    }
}