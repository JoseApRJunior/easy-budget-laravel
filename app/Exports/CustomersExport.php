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
        $commonData = $customer->commonData;
        $contact = $customer->contact;
        $address = $customer->address;
        $businessData = $customer->businessData;

        return [
            $customer->id,
            $commonData ? ($commonData->first_name . ' ' . $commonData->last_name) : '-',
            $contact ? ($contact->email_personal ?: $contact->email_business) : '-',
            $contact ? ($contact->phone_personal ?: $contact->phone_business) : '-',
            $commonData ? ($commonData->cpf ?: $commonData->cnpj) : '-',
            $commonData ? $this->getTypeLabel($commonData->type) : '-',
            $commonData->company_name ?? '-',
            $businessData->fantasy_name ?? '-',
            $businessData->state_registration ?? '-',
            $businessData->municipal_registration ?? '-',
            $commonData->birth_date ? \Carbon\Carbon::parse($commonData->birth_date)->format('d/m/Y') : '-',
            $customer->tenant ? $customer->tenant->name : 'Global',
            $address->cep ?? '-',
            $address->address ?? '-',
            $address->address_number ?? '-',
            '-', // Complemento não encontrado no model Address atual
            $address->neighborhood ?? '-',
            $address->city ?? '-',
            $address->state ?? '-',
            $customer->status === 'active' ? 'Sim' : 'Não',
            $businessData->notes ?? '-',
            $customer->budgets_count ?? 0,
            $customer->services_count ?? 0,
            $customer->invoices_count ?? 0,
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
