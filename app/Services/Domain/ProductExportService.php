<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Support\ServiceResult;
use App\Enums\OperationStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExportService
{
    /**
     * Exporta produtos para Excel ou CSV usando PhpSpreadsheet.
     */
    public function exportToExcel(Collection $products, string $format = 'xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['Produto', 'SKU', 'Categoria', 'Preço', 'Estoque', 'Situação', 'Data Criação'];
        $sheet->fromArray([$headers]);

        $row = 2;
        foreach ($products as $product) {
            $createdAt = $product->created_at ? $product->created_at->format('d/m/Y H:i:s') : '';
            $price     = 'R$ ' . number_format((float) $product->price, 2, ',', '.');
            $stock     = $product->total_stock; // Accessor do Model
            $category  = $product->category ? $product->category->name : '-';

            // Determina a situação: Deletado > Inativo > Ativo
            $situacao = !is_null($product->deleted_at) ? 'Deletado' : ($product->active ? 'Ativo' : 'Inativo');

            $dataRow = [
                $product->name,
                $product->sku ?? '-',
                $category,
                $price,
                $stock,
                $situacao,
                $createdAt,
            ];

            $sheet->fromArray([$dataRow], null, 'A' . $row);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Centralizar colunas "Situação" (F)
        $sheet->getStyle('F1:F' . ($row - 1))
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Alinhar Preço (D) e Estoque (E) à direita
        $sheet->getStyle('D1:E' . ($row - 1))
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        $fileName = "products.{$format}";
        $contentType = $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return response()->streamDownload(function () use ($spreadsheet, $format) {
            $writer = $format === 'csv' ? new Csv($spreadsheet) : new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * Exporta produtos para PDF usando Mpdf.
     */
    public function exportToPdf(Collection $products): StreamedResponse
    {
        $html = $this->generateHtmlForPdf($products);

        return response()->streamDownload(function () use ($html) {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L', // Landscape para caber mais colunas
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 15,
                'margin_bottom' => 15,
            ]);
            $mpdf->WriteHTML($html);
            echo $mpdf->Output('', 'S');
        }, 'products.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Gera o HTML para o PDF.
     */
    private function generateHtmlForPdf(Collection $products): string
    {
        $rows = '';
        foreach ($products as $product) {
            $createdAt = $product->created_at ? $product->created_at->format('d/m/Y H:i:s') : '';
            $price     = 'R$ ' . number_format((float) $product->price, 2, ',', '.');
            $stock     = intval($product->total_stock); // Mostra inteiro no PDF para economizar espaço
            $category  = $product->category ? $product->category->name : '-';

            // Determina a situação: Deletado > Inativo > Ativo
            $situacao = !is_null($product->deleted_at) ? 'Deletado' : ($product->active ? 'Ativo' : 'Inativo');

            $rows .= "<tr>
                <td>" . e($product->name) . "</td>
                <td>" . e($product->sku ?? '-') . "</td>
                <td>" . e($category) . "</td>
                <td style='text-align:right'>{$price}</td>
                <td style='text-align:right'>{$stock}</td>
                <td style='text-align:center'>{$situacao}</td>
                <td>{$createdAt}</td>
            </tr>";
        }

        $thead = '<thead><tr>
            <th>Produto</th>
            <th>SKU</th>
            <th>Categoria</th>
            <th style="text-align:right">Preço</th>
            <th style="text-align:right">Estoque</th>
            <th style="text-align:center">Situação</th>
            <th>Criação</th>
        </tr></thead>';

        return "<html><head><meta charset='utf-8'><style>
            table{border-collapse:collapse;width:100%;font-size:11px}
            th,td{border:1px solid #ddd;padding:6px;text-align:left}
            th{background:#f5f5f5}
        </style></head><body>
            <h3>Relatório de Produtos</h3>
            <table>{$thead}<tbody>{$rows}</tbody></table>
        </body></html>";
    }
}
