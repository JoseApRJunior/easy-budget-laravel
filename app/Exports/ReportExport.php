<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct(array $exportData)
    {
        $this->data = $exportData;
    }

    public function collection()
    {
        return $this->data['data'];
    }

    public function headings(): array
    {
        return $this->data['columns'] ?? [];
    }

    public function map($row): array
    {
        // Se for um modelo Eloquent, converte para array
        if (is_object($row) && method_exists($row, 'toArray')) {
            $row = $row->toArray();
        }

        // Filtra apenas os campos que estão nos cabeçalhos se necessário
        // Por simplicidade, retornamos tudo como array
        return (array) $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
