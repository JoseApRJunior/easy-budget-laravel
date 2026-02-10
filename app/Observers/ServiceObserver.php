<?php

declare(strict_types=1);

namespace App\Observers;

use App\DTOs\Invoice\InvoiceFromServiceDTO;
use App\Enums\ServiceStatus;
use App\Events\StatusUpdated;
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
     * Handle the Service "saved" event.
     */
    public function saved(Service $service): void
    {
        if ($service->budget_id) {
            $this->updateBudgetTotal($service->budget_id);
        }
    }

    /**
     * Handle the Service "deleted" event.
     */
    public function deleted(Service $service): void
    {
        if ($service->budget_id) {
            $this->updateBudgetTotal($service->budget_id);
        }
    }

    /**
     * Handle the Service "updated" event.
     * Gera fatura automaticamente quando o serviço muda para status "completed"
     */
    public function updated(Service $service): void
    {
        Log::info('ServiceObserver updated method called', [
            'service_id' => $service->id,
            'service_code' => $service->code,
            'status' => $service->status->value,
            'is_dirty' => $service->isDirty('status'),
            'original_status' => $service->getOriginal('status'),
        ]);

        // Disparar evento de notificação se o status mudou
        if ($service->isDirty('status') && ! $service->suppressStatusNotification) {
            $oldStatus = $service->getOriginal('status');
            $newStatus = $service->status;

            // Não notificar se o novo status for DRAFT (rascunho)
            if ($newStatus === ServiceStatus::DRAFT) {
                Log::info('Service notification suppressed: Status changed to DRAFT', [
                    'service_id' => $service->id
                ]);
                return;
            }

            $oldStatusValue = $oldStatus instanceof \UnitEnum ? $oldStatus->value : (string) $oldStatus;

            event(new StatusUpdated(
                $service,
                $oldStatusValue,
                $newStatus->value,
                $newStatus->label(),
                $service->tenant
            ));
        }

        // Sincronizar o total do orçamento sempre que o serviço for atualizado
        if ($service->isDirty('total') || $service->isDirty('final_total')) {
            $this->updateBudgetTotal($service->budget_id);
        }
    }

    /**
     * Atualiza o total do orçamento pai.
     */
    private function updateBudgetTotal(int $budgetId): void
    {
        $budget = \App\Models\Budget::find($budgetId);
        if ($budget) {
            $total = $budget->services()
                ->whereNotIn('status', [
                    ServiceStatus::CANCELLED->value,
                    ServiceStatus::NOT_PERFORMED->value,
                    ServiceStatus::EXPIRED->value,
                ])
                ->get()
                ->sum(function ($service) {
                    return $service->final_total ?? $service->total;
                });

            $budget->update(['total' => $total]);

            Log::info('Budget total synchronized via ServiceObserver (filtered by active services)', [
                'budget_id' => $budgetId,
                'new_total' => $total,
            ]);
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
                'tenant_id' => $service->tenant_id,
            ]);

            // Verificar se já existe uma fatura para este serviço
            if ($this->invoiceService->checkExistingInvoiceForService($service->id)) {
                Log::info('Fatura já existe para este serviço, ignorando geração automática', [
                    'service_id' => $service->id,
                    'service_code' => $service->code,
                ]);

                return;
            }

            // Preparar dados para a fatura automática
            $invoiceDTO = InvoiceFromServiceDTO::fromRequest([
                'service_code' => $service->code,
                'issue_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'), // 30 dias para pagamento
                'notes' => 'Fatura gerada automaticamente após conclusão do serviço',
                'is_automatic' => true, // Marcar como fatura automática
            ]);

            // Gerar a fatura
            $result = $this->invoiceService->createInvoiceFromService($invoiceDTO);

            if ($result->isSuccess()) {
                $invoice = $result->getData();
                Log::info('Fatura automática gerada com sucesso', [
                    'service_id' => $service->id,
                    'service_code' => $service->code,
                    'invoice_id' => $invoice->id,
                    'invoice_code' => $invoice->code,
                ]);
            } else {
                Log::error('Erro ao gerar fatura automática', [
                    'service_id' => $service->id,
                    'service_code' => $service->code,
                    'error' => $result->getMessage(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Exceção ao gerar fatura automática', [
                'service_id' => $service->id,
                'service_code' => $service->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
