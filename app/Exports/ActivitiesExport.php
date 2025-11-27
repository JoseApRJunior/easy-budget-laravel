<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivitiesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $activities;

    public function __construct($activities)
    {
        $this->activities = $activities;
    }

    public function collection()
    {
        return $this->activities;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nome',
            'Descrição',
            'Categoria',
            'Tenant',
            'Código',
            'Tipo',
            'Ativo',
            'Preço',
            'Custo',
            'Duração',
            'Unidade',
            'Cor',
            'Ícone',
            'Ordem',
            'Meta Título',
            'Meta Descrição',
            'Tags',
            'Requisitos',
            'Total Produtos',
            'Total Serviços',
            'Data Criação',
            'Data Atualização',
        ];
    }

    public function map($activity): array
    {
        return [
            $activity->id,
            $activity->name,
            $activity->description,
            $activity->category ? $activity->category->name : '-',
            $activity->tenant ? $activity->tenant->name : 'Global',
            $activity->code,
            $this->getTypeLabel($activity->type),
            $activity->is_active ? 'Sim' : 'Não',
            $activity->price ? 'R$ '.number_format($activity->price, 2, ',', '.') : '-',
            $activity->cost ? 'R$ '.number_format($activity->cost, 2, ',', '.') : '-',
            $activity->duration ? $activity->duration.' '.($activity->unit ?? 'un') : '-',
            $activity->unit ?? '-',
            $activity->color ?? '-',
            $activity->icon ?? '-',
            $activity->order ?? 0,
            $activity->meta_title ?? '-',
            $activity->meta_description ?? '-',
            $activity->tags ?? '-',
            $activity->requirements ?? '-',
            $activity->products_count,
            $activity->services_count,
            $activity->created_at->format('d/m/Y H:i:s'),
            $activity->updated_at->format('d/m/Y H:i:s'),
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
            'production' => 'Produção',
            'service' => 'Serviço',
            'consulting' => 'Consultoria',
            'training' => 'Treinamento',
            'maintenance' => 'Manutenção',
            'development' => 'Desenvolvimento',
            'marketing' => 'Marketing',
            'sales' => 'Vendas',
            'support' => 'Suporte',
            'other' => 'Outro',
        ];

        return $types[$type] ?? ucfirst($type);
    }
}
