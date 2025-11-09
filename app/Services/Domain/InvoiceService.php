<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;

class InvoiceService extends AbstractBaseService
{
    private InvoiceRepository $invoiceRepository;

    public function __construct(InvoiceRepository $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function findByCode(string $code, array $with = []): ServiceResult
    {
        try {
            $query = Invoice::where('code', $code);

            if (! empty($with)) {
                $query->with($with);
            }

            $invoice = $query->first();

            if (! $invoice) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Fatura com código {$code} não encontrada",
                );
            }

            return $this->success($invoice, 'Fatura encontrada');

        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar fatura',
                null,
                $e,
            );
        }
    }

    public function getFilteredInvoices(array $filters = [], array $with = []): ServiceResult
    {
        try {
            $invoices = $this->invoiceRepository->getFiltered($filters, ['due_date' => 'desc'], 15);

            return $this->success($invoices, 'Faturas filtradas');

        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao filtrar faturas',
                null,
                $e,
            );
        }
    }
}
