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
            'Descrição',
            'Tipo',
            'Categoria Pai',
            'Tenant',
            'Slug',
            'Ativo',
            'Cor',
            'Ícone',
            'Ordem',
            'Meta Título',
            'Meta Descrição',
            'Total Subcategorias',
            'Total Atividades',
            'Data Criação',
            'Data Atualização',
        ];
    }

    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->description,
            $this->getTypeLabel($category->type),
            $category->parent ? $category->parent->name : '-',
            $category->tenant ? $category->tenant->name : 'Global',
            $category->slug,
            $category->is_active ? 'Sim' : 'Não',
            $category->color ?? '-',
            $category->icon ?? '-',
            $category->order ?? 0,
            $category->meta_title ?? '-',
            $category->meta_description ?? '-',
            $category->children_count,
            $category->activities_count,
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
        $types = [
            'product' => 'Produto',
            'service' => 'Serviço',
            'expense' => 'Despesa',
            'income' => 'Receita',
            'asset' => 'Ativo',
            'liability' => 'Passivo',
            'equity' => 'Patrimônio Líquido',
            'other' => 'Outro',
        ];

        return $types[$type] ?? ucfirst($type);
    }
}
