<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Actions\Inventory\ReserveProductStockAction;
use App\Models\Budget;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;
use Exception;

class ReserveBudgetProductsAction
{
    public function __construct(
        private ReserveProductStockAction $reserveAction
    ) {}

    /**
     * Reserva todos os produtos contidos nos itens dos serviços do orçamento.
     */
    public function execute(Budget $budget): ServiceResult
    {
        try {
            // Verifica se já existe uma reserva registrada para este orçamento
            // para evitar duplicidade em reenvios.
            $alreadyReserved = $budget->actionHistory()
                ->whereIn('action', ['products_reserved', 'sent_and_reserved'])
                ->exists();

            if ($alreadyReserved) {
                return ServiceResult::success(null, "Produtos já estão reservados para este orçamento.");
            }

            // Carregar relações necessárias para evitar N+1
            $budget->loadMissing(['services.serviceItems.product']);

            return DB::transaction(function () use ($budget) {
                $reservedCount = 0;

                foreach ($budget->services as $service) {
                    // Identificador amigável do serviço para a mensagem de erro
                    $serviceName = $service->category?->name ?? $service->description ?? "Serviço #{$service->id}";

                    foreach ($service->serviceItems as $item) {
                        if ($item->product_id && $item->product) {
                            try {
                                $this->reserveAction->reserve($item->product, (int) $item->quantity);
                                $reservedCount++;
                            } catch (Exception $e) {
                                // Lança uma nova exceção com o contexto do serviço
                                throw new Exception("No serviço '{$serviceName}': {$e->getMessage()}");
                            }
                        }
                    }
                }

                if ($reservedCount === 0) {
                    return ServiceResult::error('Nenhum produto encontrado para reserva neste orçamento.');
                }

                // Registrar histórico
                if (method_exists($budget, 'actionHistory')) {
                    $budget->actionHistory()->create([
                        'tenant_id' => $budget->tenant_id,
                        'action' => 'products_reserved',
                        'description' => "Reserva de estoque realizada para {$reservedCount} itens do orçamento.",
                        'user_id' => auth()->id(),
                    ]);
                }

                return ServiceResult::success(null, "Reserva de estoque concluída para {$reservedCount} itens.");
            });

        } catch (Exception $e) {
            return ServiceResult::error($e->getMessage());
        }
    }
}
