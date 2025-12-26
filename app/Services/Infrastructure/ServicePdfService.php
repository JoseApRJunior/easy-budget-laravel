<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Models\Service;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;

class ServicePdfService
{
    public function generatePdf(Service $service, array $extras = []): string
    {
        $html = View::make('pages.service.public.print', array_merge(compact('service'), $extras))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ]);

        $mpdf->WriteHTML($html);
        $pdfContent = $mpdf->Output('', 'S');

        $filename = "service_{$service->code}.pdf";
        $path = "services/{$filename}";
        Storage::put($path, $pdfContent);

        return $path;
    }

    public function generateHash(string $pdfPath): string
    {
        $content = Storage::get($pdfPath);

        return hash('sha256', $content);
    }
}
