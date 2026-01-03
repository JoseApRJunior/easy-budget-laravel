<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\BudgetStatus;
use App\Enums\ServiceStatus;
use App\Models\Budget;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Services\Domain\InventoryService;
use Illuminate\Support\Facades\Log;

class InventoryObserver
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Handle the "updated" event for Budget or Service models.
     * Gerencia estoque quando orçamento ou serviço muda de status.
     */
    public function updated(Budget|Service $model): void
    {
        // Delegar para o método específico baseado no tipo do modelo
        if ($model instanceof Budget) {
            $this->handleBudgetUpdated($model);
        } elseif ($model instanceof Service) {
            $this->handleServiceUpdated($model);
        }
    }

    /**
     * Handle the Budget "updated" event.
     * Gerencia estoque quando orçamento muda de status.
     */
    protected function handleBudgetUpdated(Budget $budget): void
    {
        // Verificar se o status mudou
        if (! $budget->isDirty('status')) {
            return;
        }

        $oldStatus = $budget->getOriginal('status');
        $newStatus = $budget->status;

        Log::info('Budget status changed', [
            'budget_id' => $budget->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus->value,
            'tenant_id' => $budget->tenant_id,
        ]);

        // Se orçamento foi cancelado, devolver produtos ao estoque
        if ($newStatus->value === BudgetStatus::CANCELLED->value) {
            $this->returnBudgetItemsToInventory($budget);
        }

        // Se orçamento foi aprovado, reservar produtos do estoque
        if (
            $newStatus->value === BudgetStatus::APPROVED->value &&
            in_array($oldStatus, [BudgetStatus::DRAFT->value, BudgetStatus::PENDING->value])
        ) {
            $this->reserveBudgetItemsFromInventory($budget);
        }
    }

    /**
     * Handle the Service "updated" event.
     * Gerencia estoque quando serviço muda de status.
     */
    protected function handleServiceUpdated(Service $service): void
    {
        // Verificar se o status mudou
        if (! $service->isDirty('status')) {
            return;
        }

        $oldStatus = $service->getOriginal('status');
        $newStatus = $service->status;

        Log::info('Service status changed', [
            'service_id' => $service->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus->value,
            'tenant_id' => $service->tenant_id,
        ]);

        // Se serviço foi cancelado, devolver produtos ao estoque
        if ($newStatus->value === ServiceStatus::CANCELLED->value) {
            $this->returnServiceItemsToInventory($service);
        }

        // Se serviço foi iniciado, consumir produtos do estoque
        if (
            $newStatus->value === ServiceStatus::IN_PROGRESS->value &&
            in_array($oldStatus, [ServiceStatus::PENDING->value, ServiceStatus::SCHEDULED->value])
        ) {
            $this->consumeServiceItemsFromInventory($service);
        }
    }

    /**
     * Handle the "created" event for any observed model.
     * Quando um item é adicionado a um serviço.
     */
    public function created(Budget|Service|ServiceItem $model): void
    {
        // Apenas processar ServiceItem
        if ($model instanceof ServiceItem) {
            $this->handleServiceItemCreated($model);
        }
    }

    /**
     * Handle the "deleted" event for any observed model.
     * Quando um item é removido de um serviço.
     */
    public function deleted(Budget|Service|ServiceItem $model): void
    {
        // Apenas processar ServiceItem
        if ($model instanceof ServiceItem) {
            $this->handleServiceItemDeleted($model);
        }
    }

    /**
     * Handle the ServiceItem "created" event.
     * Quando um item é adicionado a um serviço.
     */
    protected function handleServiceItemCreated(ServiceItem $serviceItem): void
    {
        $service = $serviceItem->service;

        // Se o serviço já estiver em progresso, consumir do estoque
        if ($service->status->value === ServiceStatus::IN_PROGRESS->value) {
            $this->inventoryService->consumeProduct(
                $serviceItem->product_id,
                $serviceItem->quantity,
                'Consumo automático - Serviço: '.$service->code,
                ServiceItem::class,
                $serviceItem->id,
                $service->tenant_id,
            );
        }

        // Se o serviço estiver aprovado, reservar do estoque
        if ($service->status->value === ServiceStatus::APPROVED->value) {
            $this->inventoryService->reserveProduct(
                $serviceItem->product_id,
                $serviceItem->quantity,
                'Reserva automática - Serviço: '.$service->code,
                ServiceItem::class,
                $serviceItem->id,
                $service->tenant_id,
            );
        }
    }

    /**
     * Handle the ServiceItem "deleted" event.
     * Quando um item é removido de um serviço.
     */
    protected function handleServiceItemDeleted(ServiceItem $serviceItem): void
    {
        $service = $serviceItem->service;

        // Se o serviço estava em progresso, devolver ao estoque
        if ($service->status->value === ServiceStatus::IN_PROGRESS->value) {
            $this->inventoryService->returnProduct(
                $serviceItem->product_id,
                $serviceItem->quantity,
                'Devolução automática - Remoção do Serviço: '.$service->code,
                ServiceItem::class,
                $serviceItem->id,
                $service->tenant_id,
            );
        }

        // Se o serviço estava aprovado, liberar reserva
        if ($service->status->value === ServiceStatus::APPROVED->value) {
            $this->inventoryService->releaseReservation(
                $serviceItem->product_id,
                $serviceItem->quantity,
                'Liberação de reserva - Remoção do Serviço: '.$service->code,
                ServiceItem::class,
                $serviceItem->id,
                $service->tenant_id,
            );
        }
    }


    /**
     * Devolve itens do orçamento ao estoque
     */
    protected function returnBudgetItemsToInventory(Budget $budget): void
    {
        try {
            foreach ($budget->services as $service) {
                $this->returnServiceItemsToInventory($service);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao devolver itens do orçamento ao estoque', [
                'budget_id' => $budget->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reserva itens do orçamento no estoque
     */
    protected function reserveBudgetItemsFromInventory(Budget $budget): void
    {
        try {
            foreach ($budget->services as $service) {
                foreach ($service->serviceItems as $item) {
                    if ($item->product_id) {
                        $this->inventoryService->reserveProduct(
                            $item->product_id,
                            $item->quantity,
                            'Aprovação de orçamento - Código: '.$budget->code,
                            ServiceItem::class,
                            $item->id,
                            $budget->tenant_id,
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Erro ao reservar itens do orçamento no estoque', [
                'budget_id' => $budget->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Devolve itens do serviço ao estoque
     */
    protected function returnServiceItemsToInventory(Service $service): void
    {
        try {
            foreach ($service->serviceItems as $item) {
                if ($item->product_id) {
                    $this->inventoryService->returnProduct(
                        $item->product_id,
                        $item->quantity,
                        'Cancelamento de serviço - Código: '.$service->code,
                        Service::class,
                        $service->id,
                        $service->tenant_id,
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Erro ao devolver itens do serviço ao estoque', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Consome itens do serviço no estoque
     */
    protected function consumeServiceItemsFromInventory(Service $service): void
    {
        try {
            foreach ($service->serviceItems as $item) {
                if ($item->product_id) {
                    $this->inventoryService->consumeProduct(
                        $item->product_id,
                        $item->quantity,
                        'Início de serviço - Código: '.$service->code,
                        Service::class,
                        $service->id,
                        $service->tenant_id,
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Erro ao consumir itens do serviço no estoque', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
