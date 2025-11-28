<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoriesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $categories;

    public function __construct($categories)
    {
        $this->categories = $categories;
    }

    public function collection()
    {
        return $this->categories;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nome',
            'Categoria Pai',
            'Slug',
            'Ativo',
            'Subcategorias Ativas',
            'Data Criação',
            'Data Atualização',
        ];
    }

    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->parent ? $category->parent->name : '-',
            $category->slug,
            $category->is_active ? 'Sim' : 'Não',
            method_exists($category, 'getActiveChildrenCountAttribute')
                ? $category->active_children_count
                : $category->children()->where('is_active', true)->count(),
            $category->created_at->format('d/m/Y H:i:s'),
            $category->updated_at->format('d/m/Y H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    protected function getTypeLabel($type): string
    {
        return is_string($type) ? ucfirst($type) : '-';
    }
}
