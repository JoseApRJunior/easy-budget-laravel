<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Models\Budget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;

class BudgetPdfService
{
    public function generatePdf(Budget $budget, array $extras = []): string
    {
        $viewData = array_merge(compact('budget'), $extras);
        $html = View::make('pages.budget.pdf_budget', $viewData)->render();

        $margins = config('theme.pdf.margins');

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => $margins['left'],
            'margin_right' => $margins['right'],
            'margin_top' => $margins['top'],
            'margin_bottom' => $margins['bottom'],
            'margin_header' => $margins['header'],
            'margin_footer' => $margins['footer'],
        ]);

        $mpdf->WriteHTML($html);
        $pdfContent = $mpdf->Output('', 'S');

        $filename = "budget_{$budget->code}.pdf";
        $path = "budgets/{$filename}";
        Storage::put($path, $pdfContent);

        return $path;
    }

    public function generateHash(string $pdfPath): string
    {
        $content = Storage::get($pdfPath);

        return hash('sha256', $content);
    }
}
