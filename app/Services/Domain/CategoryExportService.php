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

class CategoryExportService
{
    /**
     * Exporta categorias para Excel ou CSV usando PhpSpreadsheet.
     */
    public function exportToExcel(Collection $categories, string $format = 'xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['Categoria', 'Subcategoria', 'Slug', 'Ativo', 'Subcategorias Ativas', 'Data Criação', 'Data Atualização'];
        $sheet->fromArray([$headers]);

        $row = 2;
        foreach ($categories as $category) {
            $createdAt       = $category->created_at ? $category->created_at->format('d/m/Y H:i:s') : '';
            $updatedAt       = $category->updated_at ? $category->updated_at->format('d/m/Y H:i:s') : '';
            $categoryName    = $category->parent_id ? ($category->parent->name ?? '-') : $category->name;
            $subcategoryName = $category->parent_id ? $category->name : '—';
            $childrenCount   = $category->children()->where('is_active', true)->count();

            $dataRow = [
                $categoryName,
                $subcategoryName,
                $category->slug ?: Str::slug($category->name),
                $category->is_active ? 'Sim' : 'Não',
                $childrenCount,
                $createdAt,
                $updatedAt,
            ];

            $sheet->fromArray([$dataRow], null, 'A' . $row);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "categories.{$format}";
        $contentType = $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return response()->streamDownload(function () use ($spreadsheet, $format) {
            $writer = $format === 'csv' ? new Csv($spreadsheet) : new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * Exporta categorias para PDF usando Mpdf.
     */
    public function exportToPdf(Collection $categories): StreamedResponse
    {
        $html = $this->generateHtmlForPdf($categories);

        return response()->streamDownload(function () use ($html) {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 15,
                'margin_bottom' => 15,
            ]);
            $mpdf->WriteHTML($html);
            echo $mpdf->Output('', 'S');
        }, 'categories.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Gera o HTML para o PDF.
     */
    private function generateHtmlForPdf(Collection $categories): string
    {
        $rows = '';
        foreach ($categories as $category) {
            $createdAt       = $category->created_at ? $category->created_at->format('d/m/Y H:i:s') : '';
            $updatedAt       = $category->updated_at ? $category->updated_at->format('d/m/Y H:i:s') : '';
            $categoryName    = $category->parent_id ? ($category->parent->name ?? '-') : $category->name;
            $subcategoryName = $category->parent_id ? $category->name : '—';
            $childrenCount   = $category->children()->where('is_active', true)->count();

            $rows .= "<tr>
                <td>" . e($categoryName) . "</td>
                <td>" . e($subcategoryName) . "</td>
                <td>" . e((string)($category->slug ?: Str::slug($category->name))) . "</td>
                <td>" . ($category->is_active ? 'Sim' : 'Não') . "</td>
                <td style='text-align:center'>{$childrenCount}</td>
                <td>{$createdAt}</td>
                <td>{$updatedAt}</td>
            </tr>";
        }

        $thead = '<thead><tr>
            <th>Categoria</th>
            <th>Subcategoria</th>
            <th>Slug</th>
            <th>Ativo</th>
            <th style="text-align:center">Subcats Ativas</th>
            <th>Criação</th>
            <th>Atualização</th>
        </tr></thead>';

        return "<html><head><meta charset='utf-8'><style>
            table{border-collapse:collapse;width:100%;font-size:11px}
            th,td{border:1px solid #ddd;padding:6px;text-align:left}
            th{background:#f5f5f5}
        </style></head><body>
            <h3>Relatório de Categorias</h3>
            <table>{$thead}<tbody>{$rows}</tbody></table>
        </body></html>";
    }
}
