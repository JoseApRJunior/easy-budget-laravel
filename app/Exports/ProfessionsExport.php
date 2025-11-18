<?php

namespace App\Exports;

use App\Models\Profession;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfessionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $professions;

    public function __construct($professions)
    {
        $this->professions = $professions;
    }

    public function collection()
    {
        return $this->professions;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nome',
            'Descrição',
            'Tipo',
            'Tenant',
            'Código',
            'Ativo',
            'Cor',
            'Ícone',
            'Ordem',
            'Meta Título',
            'Meta Descrição',
            'Requisitos',
            'Certificações',
            'Habilidades',
            'Salário Médio',
            'Mercado de Trabalho',
            'Nível de Educação',
            'Total Usuários',
            'Total Fornecedores',
            'Data Criação',
            'Data Atualização',
        ];
    }

    public function map($profession): array
    {
        return [
            $profession->id,
            $profession->name,
            $profession->description,
            $this->getTypeLabel($profession->type),
            $profession->tenant ? $profession->tenant->name : 'Global',
            $profession->code,
            $profession->is_active ? 'Sim' : 'Não',
            $profession->color ?? '-',
            $profession->icon ?? '-',
            $profession->order ?? 0,
            $profession->meta_title ?? '-',
            $profession->meta_description ?? '-',
            $profession->requirements ?? '-',
            $profession->certifications ?? '-',
            $profession->skills ?? '-',
            $profession->average_salary ? 'R$ ' . number_format($profession->average_salary, 2, ',', '.') : '-',
            $this->getJobMarketLabel($profession->job_market),
            $this->getEducationLevelLabel($profession->education_level),
            $profession->users_count,
            $profession->providers_count,
            $profession->created_at->format('d/m/Y H:i:s'),
            $profession->updated_at->format('d/m/Y H:i:s'),
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
            'health' => 'Saúde',
            'technology' => 'Tecnologia',
            'education' => 'Educação',
            'engineering' => 'Engenharia',
            'business' => 'Negócios',
            'arts' => 'Artes',
            'sciences' => 'Ciências',
            'services' => 'Serviços',
            'trades' => 'Ofícios',
            'administration' => 'Administração',
            'legal' => 'Jurídico',
            'finance' => 'Finanças',
            'marketing' => 'Marketing',
            'sales' => 'Vendas',
            'other' => 'Outro',
        ];

        return $types[$type] ?? ucfirst($type);
    }

    protected function getJobMarketLabel($market): string
    {
        $markets = [
            'high_demand' => 'Alta Demanda',
            'medium_demand' => 'Demanda Média',
            'low_demand' => 'Baixa Demanda',
            'stable' => 'Estável',
            'growing' => 'Em Crescimento',
            'declining' => 'Em Declínio',
        ];

        return $markets[$market] ?? ucfirst(str_replace('_', ' ', $market));
    }

    protected function getEducationLevelLabel($level): string
    {
        $levels = [
            'elementary' => 'Ensino Fundamental',
            'high_school' => 'Ensino Médio',
            'technical' => 'Técnico',
            'bachelor' => 'Graduação',
            'specialization' => 'Especialização',
            'master' => 'Mestrado',
            'doctorate' => 'Doutorado',
            'post_doctorate' => 'Pós-Doutorado',
        ];

        return $levels[$level] ?? ucfirst(str_replace('_', ' ', $level));
    }
}