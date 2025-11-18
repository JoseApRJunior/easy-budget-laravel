<?php

namespace App\Exports;

use App\Models\Provider;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProvidersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $providers;

    public function __construct($providers)
    {
        $this->providers = $providers;
    }

    public function collection()
    {
        return $this->providers;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nome',
            'Email',
            'Telefone',
            'Documento',
            'Nome da Empresa',
            'Nome Fantasia',
            'Inscrição Estadual',
            'Inscrição Municipal',
            'Tenant',
            'Plano',
            'CEP',
            'Endereço',
            'Número',
            'Complemento',
            'Bairro',
            'Cidade',
            'Estado',
            'Ativo',
            'Notas',
            'Total Clientes',
            'Total Orçamentos',
            'Total Serviços',
            'Total Faturas',
            'Data Criação',
            'Data Atualização',
        ];
    }

    public function map($provider): array
    {
        return [
            $provider->id,
            $provider->name,
            $provider->email,
            $provider->phone ?? '-',
            $provider->document ?? '-',
            $provider->company_name ?? '-',
            $provider->trading_name ?? '-',
            $provider->state_registration ?? '-',
            $provider->municipal_registration ?? '-',
            $provider->tenant ? $provider->tenant->name : 'Global',
            $provider->plan ? $provider->plan->name : '-',
            $provider->zip_code ?? '-',
            $provider->address ?? '-',
            $provider->number ?? '-',
            $provider->complement ?? '-',
            $provider->neighborhood ?? '-',
            $provider->city ? $provider->city->name : '-',
            $provider->state ? $provider->state->name : '-',
            $provider->is_active ? 'Sim' : 'Não',
            $provider->notes ?? '-',
            $provider->customers_count,
            $provider->budgets_count,
            $provider->services_count,
            $provider->invoices_count,
            $provider->created_at->format('d/m/Y H:i:s'),
            $provider->updated_at->format('d/m/Y H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}