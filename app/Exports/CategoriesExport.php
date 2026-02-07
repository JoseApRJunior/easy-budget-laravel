<?php

namespace App\Exports;

use Illuminate\Support\Str;
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
            'Categoria',
            'Subcategoria',
            'Slug',
            'Ativo',
            'Subcategorias Ativas',
            'Data Criação',
            'Data Atualização',
        ];
    }

    public function map($category): array
    {
        $categoryName = $category->parent_id ? ($category->parent->name ?? '-') : $category->name;
        $subcategoryName = $category->parent_id ? $category->name : '—';

        return [
            $categoryName,
            $subcategoryName,
            $category->slug ?: Str::slug($category->name),
            $category->is_active ? 'Sim' : 'Não',
            $category->children()->where('is_active', true)->count(),
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
