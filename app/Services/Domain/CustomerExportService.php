<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Services\Domain\Abstracts\AbstractExportService;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerExportService extends AbstractExportService
{
    protected function getHeaders(): array
    {
        return ['ID', 'Tipo', 'Nome/Razão Social', 'CPF/CNPJ', 'Email', 'Telefone', 'Cidade/UF', 'Situação', 'Data Cadastro'];
    }

    protected function getExportTitle(): string
    {
        return 'Relatório de Clientes';
    }

    protected function mapData(mixed $customer): array
    {
        /** @var \App\Models\Customer $customer */
        $commonData = $customer->commonData;
        $contact = $customer->contact;
        $address = $customer->address;

        $createdAt = $customer->created_at ? $customer->created_at->format('d/m/Y H:i:s') : '';
        $type = ($commonData->type ?? 'individual') === 'individual' ? 'PF' : 'PJ';
        $name = $commonData->company_name ?: (($commonData->first_name ?? '').' '.($commonData->last_name ?? ''));
        $document = $commonData->cpf ?: ($commonData->cnpj ?? '-');
        $email = $contact->email_personal ?: ($contact->email_business ?? '-');
        $phone = $contact->phone_personal ?: ($contact->phone_business ?? '-');
        $location = ($address->city ?? '').'/'.($address->state ?? '');

        // Determina a situação: Deletado > Inativo > Ativo
        $situacao = ! is_null($customer->deleted_at) ? 'Deletado' : ($customer->status === 'active' ? 'Ativo' : 'Inativo');

        return [
            $customer->id,
            $type,
            trim($name),
            $document,
            $email,
            $phone,
            $location,
            $situacao,
            $createdAt,
        ];
    }

    private array $filters = [];

    public function exportToExcel(Collection $customers, string $format = 'xlsx', string $fileName = 'clientes'): StreamedResponse
    {
        return parent::exportToExcel($customers, $format, $fileName);
    }

    public function exportToPdf(Collection $customers, string $fileName = 'clientes', string $orientation = 'A4-L', array $filters = []): StreamedResponse
    {
        $this->filters = $filters;

        return parent::exportToPdf($customers, $fileName, $orientation);
    }

    protected function getPdfViewName(): ?string
    {
        // Se existir uma view específica, retornamos aqui. Caso contrário, usa o fallback.
        return view()->exists('pages.report.customer.pdf_customer') ? 'pages.report.customer.pdf_customer' : null;
    }

    protected function getPdfData(Collection $items): array
    {
        return [
            'company' => $this->getCompanyData(),
            'provider' => auth()->user()->provider,
            'filters' => $this->filters,
            'customers' => $items,
        ];
    }

    /**
     * Sobrescreve para aplicar estilos específicos.
     */
    protected function applyExcelStyles($sheet, int $rowCount): void
    {
        parent::applyExcelStyles($sheet, $rowCount);

        // Centralizar colunas "Tipo" (B) e "Situação" (H)
        $sheet->getStyle('B1:B'.($rowCount - 1))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('H1:H'.($rowCount - 1))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
