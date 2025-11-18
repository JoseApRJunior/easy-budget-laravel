<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Services\Domain\InvoiceService;
use Illuminate\Support\Facades\Log;

class ServiceObserver
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Handle the Service "updated" event.
     * Gera fatura automaticamente quando o serviço muda para status "completed"
     */
    public function updated(Service $service): void
    {
        // Verificar se o status mudou para "completed"
        if ($service->isDirty('status') && $service->status->value === ServiceStatus::COMPLETED->value) {
            $this->generateAutomaticInvoice($service);
        }
    }

    /**
     * Gera fatura automática para o serviço concluído
     */
    protected function generateAutomaticInvoice(Service $service): void
    {
        try {
            Log::info('Iniciando geração automática de fatura para serviço', [
                'service_id' => $service->id,
                'service_code' => $service->code,
                'tenant_id' => $service->tenant_id
            ]);

            // Verificar se já existe uma fatura para este serviço
            if ($this->invoiceService->checkExistingInvoiceForService($service->id)) {
                Log::info('Fatura já existe para este serviço, ignorando geração automática', [
                    'service_id' => $service->id,
                    'service_code' => $service->code
                ]);
                return;
            }

            // Preparar dados para a fatura automática
            $invoiceData = [
                'issue_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'), // 30 dias para pagamento
                'notes' => 'Fatura gerada automaticamente após conclusão do serviço',
                'is_automatic' => true, // Marcar como fatura automática
            ];

            // Gerar a fatura
            $result = $this->invoiceService->createInvoiceFromService($service->code, $invoiceData);

            if ($result->isSuccess()) {
                $invoice = $result->getData();
                Log::info('Fatura automática gerada com sucesso', [
                    'service_id' => $service->id,
                    'service_code' => $service->code,
                    'invoice_id' => $invoice->id,
                    'invoice_code' => $invoice->code
                ]);
            } else {
                Log::error('Erro ao gerar fatura automática', [
                    'service_id' => $service->id,
                    'service_code' => $service->code,
                    'error' => $result->getMessage()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Exceção ao gerar fatura automática', [
                'service_id' => $service->id,
                'service_code' => $service->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}