<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\BudgetStatus;
use App\Enums\ServiceStatus;
use App\Models\Budget;
use Illuminate\Support\Facades\Log;

class BudgetStatusObserver
{
    /**
     * Handle the Budget "updated" event.
     * Gerencia as transições em cascata para os serviços vinculados.
     */
    public function updated(Budget $budget): void
    {
        if (! $budget->isDirty('status')) {
            return;
        }

        $newStatus = $budget->status;
        $oldStatus = $budget->getOriginal('status');

        Log::info('BudgetStatusObserver: Status changed', [
            'budget_id' => $budget->id,
            'old_status' => $oldStatus instanceof BudgetStatus ? $oldStatus->value : $oldStatus,
            'new_status' => $newStatus->value,
        ]);

        $this->handleCascadeTransitions($budget, $newStatus);
    }

    /**
     * Gerencia as transições automáticas dos serviços baseadas no novo status do orçamento.
     */
    protected function handleCascadeTransitions(Budget $budget, BudgetStatus $newStatus): void
    {
        $services = $budget->services;

        if ($services->isEmpty()) {
            return;
        }

        foreach ($services as $service) {
            $newServiceStatus = null;

            switch ($newStatus) {
                case BudgetStatus::PENDING:
                    if ($service->status === ServiceStatus::DRAFT) {
                        $newServiceStatus = ServiceStatus::PENDING;
                    }
                    break;

                case BudgetStatus::APPROVED:
                    if ($service->status === ServiceStatus::PENDING) {
                        $newServiceStatus = ServiceStatus::SCHEDULING;
                    }
                    break;

                case BudgetStatus::REJECTED:
                    if ($service->status === ServiceStatus::PENDING) {
                        $newServiceStatus = ServiceStatus::DRAFT;
                    }
                    break;

                case BudgetStatus::CANCELLED:
                    // Se o serviço estiver em andamento, torna-se parcial. Caso contrário, é cancelado.
                    if ($service->status === ServiceStatus::IN_PROGRESS) {
                        $newServiceStatus = ServiceStatus::PARTIAL;
                    } elseif (! $service->isFinished()) {
                        $newServiceStatus = ServiceStatus::CANCELLED;
                    }
                    break;

                case BudgetStatus::DRAFT:
                    // Se o orçamento volta para rascunho (ex: após rejeição ou expiração)
                    if (! $service->isFinished()) {
                        $newServiceStatus = ServiceStatus::DRAFT;
                    }
                    break;
            }

            if ($newServiceStatus && $service->status !== $newServiceStatus) {
                Log::info('BudgetStatusObserver: Cascading status to service', [
                    'service_id' => $service->id,
                    'old_status' => $service->status->value,
                    'new_status' => $newServiceStatus->value,
                ]);

                $service->update(['status' => $newServiceStatus]);
            }
        }
    }
}
