<?php

declare(strict_types=1);

namespace App\Services\Domain\Abstracts;

use Illuminate\Support\Collection;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AbstractExportService
{
    /**
     * Define as colunas do cabeçalho.
     */
    abstract protected function getHeaders(): array;

    /**
     * Formata os dados de um item para uma linha do Excel/PDF.
     */
    abstract protected function mapData(mixed $item): array;

    /**
     * Exporta dados para Excel ou CSV.
     */
    public function exportToExcel(Collection $items, string $format = 'xlsx', string $fileName = 'export'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = $this->getHeaders();
        $sheet->fromArray([$headers]);

        $row = 2;
        foreach ($items as $item) {
            $dataRow = $this->mapData($item);
            $sheet->fromArray([$dataRow], null, 'A'.$row);
            $row++;
        }

        $this->applyExcelStyles($sheet, $row);

        $cleanFileName = str_replace(['/', '\\', '.', ' '], '_', $fileName);
        $fileNameWithExt = "{$cleanFileName}.{$format}";

        $contentType = $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return \response()->streamDownload(function () use ($spreadsheet, $format) {
            $writer = $format === 'csv' ? new Csv($spreadsheet) : new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileNameWithExt, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * Aplica estilos básicos ao Excel.
     */
    protected function applyExcelStyles($sheet, int $rowCount): void
    {
        $headers = $this->getHeaders();
        $lastColumn = chr(64 + count($headers));

        foreach (range('A', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Exporta dados para PDF.
     */
    public function exportToPdf(Collection $items, string $fileName = 'export', string $orientation = 'A4'): StreamedResponse
    {
        $html = $this->generateHtmlForPdf($items);
        $cleanFileName = str_replace(['/', '\\', '.', ' '], '_', $fileName);
        $fileNameWithExt = "{$cleanFileName}.pdf";

        return \response()->streamDownload(function () use ($html, $orientation) {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => $orientation,
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 15,
                'margin_bottom' => 15,
            ]);
            $mpdf->WriteHTML($html);
            echo $mpdf->Output('', 'S');
        }, $fileNameWithExt, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Gera o HTML para o PDF usando um template Blade se disponível,
     * caso contrário usa o fallback programático.
     */
    protected function generateHtmlForPdf(Collection $items): string
    {
        $viewName = $this->getPdfViewName();

        if ($viewName && view()->exists($viewName)) {
            $pdfData = $this->getPdfData($items);

            return view($viewName, array_merge([
                'items' => $items,
                'title' => $this->getExportTitle(),
                'headers' => $this->getHeaders(),
            ], $pdfData))->render();
        }

        return $this->generateFallbackHtml($items);
    }

    /**
     * Retorna o nome da view Blade para o PDF (opcional).
     */
    protected function getPdfViewName(): ?string
    {
        return null;
    }

    /**
     * Retorna dados adicionais para a view do PDF.
     */
    protected function getPdfData(Collection $items): array
    {
        return [];
    }

    /**
     * Gera o HTML básico para o PDF (fallback).
     */
    protected function generateFallbackHtml(Collection $items): string
    {
        $headers = $this->getHeaders();
        $title = $this->getExportTitle();

        $html = "<h1>{$title}</h1>";
        $html .= '<table border="1" width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse; font-family: sans-serif; font-size: 10pt;">';
        $html .= '<thead style="background-color: #f2f2f2;"><tr>';

        foreach ($headers as $header) {
            $html .= "<th>{$header}</th>";
        }

        $html .= '</tr></thead><tbody>';

        foreach ($items as $item) {
            $html .= '<tr>';
            $dataRow = $this->mapData($item);
            foreach ($dataRow as $cell) {
                $html .= "<td>{$cell}</td>";
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Retorna os dados da empresa (tenant/provider) para o cabeçalho do PDF.
     */
    protected function getCompanyData(): array
    {
        $user = auth()->user();
        $provider = $user->provider;
        $commonData = $provider?->commonData;
        $address = $provider?->address;

        // Determina o nome baseado estritamente em CommonData ou User
        $name = 'Minha Empresa';
        if ($commonData) {
            // Prioridade: company_name -> first_name + last_name
            if (! empty($commonData->company_name)) {
                $name = $commonData->company_name;
            } elseif (! empty($commonData->first_name)) {
                $name = $commonData->first_name.($commonData->last_name ? ' '.$commonData->last_name : '');
            } else {
                $name = $user->name;
            }
        } else {
            $name = $user->name;
        }

        // Formata o endereço a partir da model Address
        $fullAddress = 'Endereço não informado';
        if ($address) {
            $fullAddress = "{$address->address}, {$address->address_number}";
            if ($address->neighborhood) {
                $fullAddress .= " - {$address->neighborhood}";
            }
            if ($address->city) {
                $fullAddress .= " - {$address->city}/{$address->state}";
            }
        }

        return [
            'name' => $name,
            'address' => $fullAddress,
            'cnpj' => $commonData?->cnpj ?? $commonData?->cpf ?? '00.000.000/0000-00',
            'logo_url' => $user->tenant->logo_url ? public_path('storage/'.$user->tenant->logo_url) : null,
        ];
    }

    /**
     * Título do documento exportado.
     */
    abstract protected function getExportTitle(): string;
}
