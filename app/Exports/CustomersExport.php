<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $customers;

    public function __construct($customers)
    {
        $this->customers = $customers;
    }

    public function collection()
    {
        return $this->customers;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nome',
            'Email',
            'Telefone',
            'Documento',
            'Tipo',
            'Nome da Empresa',
            'Nome Fantasia',
            'Inscrição Estadual',
            'Inscrição Municipal',
            'Data de Nascimento',
            'Tenant',
            'CEP',
            'Endereço',
            'Número',
            'Complemento',
            'Bairro',
            'Cidade',
            'Estado',
            'Ativo',
            'Notas',
            'Total Orçamentos',
            'Total Serviços',
            'Total Faturas',
            'Data Criação',
            'Data Atualização',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->name,
            $customer->email,
            $customer->phone ?? '-',
            $customer->document ?? '-',
            $this->getTypeLabel($customer->type),
            $customer->company_name ?? '-',
            $customer->trading_name ?? '-',
            $customer->state_registration ?? '-',
            $customer->municipal_registration ?? '-',
            $customer->birth_date ? $customer->birth_date->format('d/m/Y') : '-',
            $customer->tenant ? $customer->tenant->name : 'Global',
            $customer->zip_code ?? '-',
            $customer->address ?? '-',
            $customer->number ?? '-',
            $customer->complement ?? '-',
            $customer->neighborhood ?? '-',
            $customer->city ? $customer->city->name : '-',
            $customer->state ? $customer->state->name : '-',
            $customer->is_active ? 'Sim' : 'Não',
            $customer->notes ?? '-',
            $customer->budgets_count,
            $customer->services_count,
            $customer->invoices_count,
            $customer->created_at->format('d/m/Y H:i:s'),
            $customer->updated_at->format('d/m/Y H:i:s'),
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
        return $type === 'individual' ? 'Pessoa Física' : 'Pessoa Jurídica';
    }
}
