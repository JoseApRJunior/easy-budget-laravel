<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Models\Budget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;

class BudgetPdfService
{
    public function generatePdf( Budget $budget ): string
    {
        // Renderizar HTML do orçamento
        $html = View::make( 'budgets.pdf', compact( 'budget' ) )->render();

        // Configurar mPDF
        $mpdf = new Mpdf( [
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 16,
            'margin_bottom' => 16,
        ] );

        // Gerar PDF
        $mpdf->WriteHTML( $html );
        $pdfContent = $mpdf->Output( '', 'S' );

        // Salvar arquivo
        $filename = "budget_{$budget->code}.pdf";
        $path     = "budgets/{$filename}";

        Storage::put( $path, $pdfContent );

        return $path;
    }

    public function generateHash( string $pdfPath ): string
    {
        $content = Storage::get( $pdfPath );
        return hash( 'sha256', $content );
    }

}
