<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Enums\ServiceStatus;
use App\Models\Budget;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Domain\ScheduleService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceService extends AbstractBaseService
{
    /**
     * @var ScheduleService
     */
    protected $scheduleService;

    /**
     * ServiceService constructor.
     *
     * @param ScheduleService $scheduleService
     */
    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function findByCode( string $code, array $with = [] ): ServiceResult
    {
        try {
            // Usar withoutGlobalScopes para debug - o tenant scoping será aplicado no controller
            $query = Service::withoutGlobalScopes()->where( 'code', $code );

            if ( !empty( $with ) ) {
                $query->with( $with );
            }

            $service = $query->first();

            if ( !$service ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Serviço com código {$code} não encontrado",
                );
            }

            return $this->success( $service, 'Serviço encontrado' );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar serviço',
                null,
                $e,
            );
        }
    }

    public function getFilteredServices( array $filters = [], array $with = [] ): ServiceResult
    {
        try {
            $query = Service::query();

            // Filtros
            if ( !empty( $filters[ 'status' ] ) ) {
                $query->where( 'status', $filters[ 'status' ] );
            }

            if ( !empty( $filters[ 'category_id' ] ) ) {
                $query->where( 'category_id', $filters[ 'category_id' ] );
            }

            if ( !empty( $filters[ 'date_from' ] ) ) {
                $query->whereDate( 'created_at', '>=', $filters[ 'date_from' ] );
            }

            if ( !empty( $filters[ 'date_to' ] ) ) {
                $query->whereDate( 'created_at', '<=', $filters[ 'date_to' ] );
            }

            if ( !empty( $filters[ 'search' ] ) ) {
                $query->where( function ( $q ) use ( $filters ) {
                    $q->where( 'code', 'like', '%' . $filters[ 'search' ] . '%' )
                        ->orWhere( 'description', 'like', '%' . $filters[ 'search' ] . '%' );
                } );
            }

            // Eager loading
            $withDefaults = [ 'category', 'budget.customer', 'serviceStatus' ];
            $with         = array_unique( array_merge( $withDefaults, $with ) );
            $query->with( $with );

            // Ordenação
            $query->orderBy( 'created_at', 'desc' );

            // Paginação
            $services = $query->paginate( 15 );

            return $this->success( $services, 'Serviços filtrados' );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao filtrar serviços',
                null,
                $e,
            );
        }
    }

    /**
     * Cria um novo serviço com itens e atualiza o orçamento.
     */
    public function createService( array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($data) {
                // Buscar orçamento
                $budget = Budget::where( 'code', $data[ 'budget_code' ] )->first();
                if ( !$budget ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        'Orçamento não encontrado',
                    );
                }

                // Gerar código único
                $serviceCode = $this->generateUniqueServiceCode( $budget->code );

                // Criar serviço
                $service = Service::create( [
                    'tenant_id'   => $budget->tenant_id,
                    'budget_id'   => $budget->id,
                    'category_id' => $data[ 'category_id' ] ?? null,
                    'code'        => $serviceCode,
                    'status'      => $data[ 'status' ] ?? ServiceStatus::SCHEDULED->value,
                    'description' => $data[ 'description' ] ?? null,
                    'due_date'    => $data[ 'due_date' ] ?? null,
                    'discount'    => $data[ 'discount' ] ?? 0.0,
                    'total'       => $data[ 'total' ] ?? 0.0
                ] );

                // Criar itens do serviço
                if ( !empty( $data[ 'items' ] ) ) {
                    $this->createServiceItems( $service, $data[ 'items' ] );
                }

                // Atualizar total do orçamento
                $this->updateBudgetTotal( $budget );

                return $this->success( $service->load( [
                    'budget',
                    'serviceItems.product',
                    'category'
                ] ), 'Serviço criado com sucesso' );

            } );

        } catch ( Exception $e ) {
            // Preservar mensagens de erro específicas
            $message = $e->getMessage();
            if ( strpos( $message, 'Produto ID' ) === false ) {
                $message = 'Erro ao criar serviço';
            }

            return $this->error(
                OperationStatus::ERROR,
                $message,
                null,
                $e,
            );
        }
    }

    /**
     * Gera código único para o serviço baseado no código do orçamento.
     */
    private function generateUniqueServiceCode( string $budgetCode ): string
    {
        $lastService = Service::whereHas( 'budget', function ( $query ) use ( $budgetCode ) {
            $query->where( 'code', $budgetCode );
        } )->orderBy( 'code', 'desc' )->first();

        $sequential = 1;
        if ( $lastService && preg_match( '/-S(\d{3})$/', $lastService->code, $matches ) ) {
            $sequential = (int) $matches[ 1 ] + 1;
        }

        return $budgetCode . "-S" . str_pad( (string) $sequential, 3, '0', STR_PAD_LEFT );
    }

    /**
     * Cria itens do serviço.
     */
    private function createServiceItems( Service $service, array $items ): void
    {
        foreach ( $items as $itemData ) {
            // Validar produto
            $product = Product::where( 'id', $itemData[ 'product_id' ] )
                ->where( 'active', true )
                ->first();

            if ( !$product ) {
                throw new Exception( "Produto ID {$itemData[ 'product_id' ]} não encontrado ou inativo" );
            }

            // Calcular total do item
            $quantity  = (float) $itemData[ 'quantity' ];
            $unitValue = (float) $itemData[ 'unit_value' ];
            $total     = $quantity * $unitValue;

            // Criar item
            ServiceItem::create( [
                'tenant_id'  => $service->tenant_id,
                'service_id' => $service->id,
                'product_id' => $product->id,
                'unit_value' => $unitValue,
                'quantity'   => $quantity,
                'total'      => $total
            ] );
        }

        // Atualizar total do serviço
        $this->updateServiceTotal( $service );
    }

    /**
     * Atualiza o total do serviço baseado nos itens.
     */
    private function updateServiceTotal( Service $service ): void
    {
        $total = $service->serviceItems()->sum( 'total' );
        $service->update( [ 'total' => $total ] );
    }

    /**
     * Atualiza o total do orçamento após criar serviços.
     */
    private function updateBudgetTotal( Budget $budget ): void
    {
        $budget->updateCalculatedTotals();
    }

    /**
     * Atualiza serviço por código com gerenciamento de itens.
     */
    public function updateServiceByCode( string $code, array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($code, $data) {
                $service = Service::where( 'code', $code )->first();

                if ( !$service ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Serviço {$code} não encontrado",
                    );
                }

                // Verificar se pode editar
                if ( !$service->serviceStatus->canEdit() ) {
                    return $this->error(
                        OperationStatus::INVALID_DATA,
                        "Serviço não pode ser editado no status {$service->serviceStatus->value}",
                    );
                }

                // Atualizar serviço
                $service->update( [
                    'category_id' => $data[ 'category_id' ] ?? $service->category_id,
                    'description' => $data[ 'description' ] ?? $service->description,
                    'due_date'    => $data[ 'due_date' ] ?? $service->due_date,
                    'status'      => $data[ 'status' ] ?? $service->status,
                ] );

                // Gerenciar itens processados
                if ( !empty( $data[ 'items_to_create' ] ) || !empty( $data[ 'items_to_update' ] ) || !empty( $data[ 'items_to_delete' ] ) ) {
                    $this->updateServiceItems( $service, $data );
                }

                // Atualizar total do orçamento
                $this->updateBudgetTotal( $service->budget );

                return $this->success( $service->fresh( [
                    'serviceItems.product',
                ] ), 'Serviço atualizado' );

            } );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao atualizar serviço',
                null,
                $e,
            );
        }
    }

    /**
     * Atualiza itens do serviço (delete/update/create).
     */
    private function updateServiceItems( Service $service, array $data ): void
    {
        // Processar itens para deletar
        if ( !empty( $data[ 'items_to_delete' ] ) ) {
            ServiceItem::whereIn( 'id', $data[ 'items_to_delete' ] )
                ->where( 'service_id', $service->id )
                ->delete();
        }

        // Processar itens para atualizar
        if ( !empty( $data[ 'items_to_update' ] ) ) {
            foreach ( $data[ 'items_to_update' ] as $itemData ) {
                // Validar produto
                $product = Product::where( 'id', $itemData[ 'product_id' ] )
                    ->where( 'active', true )
                    ->first();

                if ( !$product ) {
                    throw new Exception( "Produto ID {$itemData[ 'product_id' ]} não encontrado ou inativo" );
                }

                // Calcular total do item
                $quantity  = (float) $itemData[ 'quantity' ];
                $unitValue = (float) $itemData[ 'unit_value' ];
                $total     = $quantity * $unitValue;

                // Atualizar item
                ServiceItem::where( 'id', $itemData[ 'id' ] )
                    ->where( 'service_id', $service->id )
                    ->update( [
                        'product_id' => $product->id,
                        'unit_value' => $unitValue,
                        'quantity'   => $quantity,
                        'total'      => $total,
                    ] );
            }

        }
        // Processar itens para criar
        if ( !empty( $data[ 'items_to_create' ] ) ) {
            foreach ( $data[ 'items_to_create' ] as $itemData ) {
                // Validar produto
                $product = Product::where( 'id', $itemData[ 'product_id' ] )
                    ->where( 'active', true )
                    ->first();

                if ( !$product ) {
                    throw new Exception( "Produto ID {$itemData[ 'product_id' ]} não encontrado ou inativo" );
                }

                // Calcular total do item
                $quantity  = (float) $itemData[ 'quantity' ];
                $unitValue = (float) $itemData[ 'unit_value' ];
                $total     = $quantity * $unitValue;

                // Criar item
                ServiceItem::create( [
                    'tenant_id'  => $service->tenant_id,
                    'service_id' => $service->id,
                    'product_id' => $product->id,
                    'unit_value' => $unitValue,
                    'quantity'   => $quantity,
                    'total'      => $total,
                ] );
            }
        }

        // Atualizar total do serviço
        $this->updateServiceTotal( $service );
    }

    /**
     * Altera o status do serviço com validação de transições permitidas.
     */
    public function changeStatus( string $code, string $newStatus ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($code, $newStatus) {
                $service = Service::where( 'code', $code )->first();

                if ( !$service ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Serviço {$code} não encontrado",
                    );
                }

                $oldStatus = $service->status;

                // Validar transição
                $allowedTransitions = ServiceStatus::getAllowedTransitions( $oldStatus->value );
                if ( !in_array( $newStatus, $allowedTransitions ) ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        "Transição de {$oldStatus->value} para {$newStatus} não permitida",
                    );
                }

                // Atualizar serviço
                $service->update( [ 'status' => $newStatus ] );

                // Atualizar orçamento em cascata se necessário
                $this->updateBudgetStatusIfNeeded( $service, $newStatus );

                // Carregar relacionamentos para retorno completo
                $service->load(['customer', 'category', 'serviceStatus']);

                return $this->success( $service, 'Status alterado com sucesso' );

            } );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao alterar status',
                null,
                $e,
            );
        }
    }

    /**
     * Get the latest schedule for a service.
     */
    public function getLatestSchedule(Service $service): ?\App\Models\Schedule
    {
        return $this->scheduleService->getLatestScheduleByService($service->id);
    }

    /**
     * Atualiza o status do orçamento em cascata quando necessário.
     */
    private function updateBudgetStatusIfNeeded( Service $service, string $newStatus ): void
    {
        $budgetStatusMap = [
            ServiceStatus::APPROVED->value  => 'approved',
            ServiceStatus::REJECTED->value  => 'rejected',
            ServiceStatus::CANCELLED->value => 'cancelled'
        ];

        if ( isset( $budgetStatusMap[ $newStatus ] ) ) {
            $service->budget->update( [ 'status' => $budgetStatusMap[ $newStatus ] ] );
        }
    }

    /**
     * Deleta um serviço por código com validações de dependências.
     */
    public function deleteByCode( string $code ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($code) {
                $service = Service::where( 'code', $code )->first();

                if ( !$service ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Serviço {$code} não encontrado",
                    );
                }

                // Verificar se pode deletar
                if ( !$this->canDeleteService( $service ) ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Serviço não pode ser excluído devido a dependências',
                    );
                }

                // Verificar se não tem agendamentos futuros
                $futureSchedules = $service->schedules()
                    ->where( 'start_date_time', '>', now() )
                    ->count();

                if ( $futureSchedules > 0 ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Serviço possui agendamentos futuros e não pode ser excluído',
                    );
                }

                // Deletar itens do serviço
                $service->serviceItems()->delete();

                // Deletar agendamentos
                $service->schedules()->delete();

                // Deletar o serviço
                $service->delete();

                // Atualizar total do orçamento
                $this->updateBudgetTotal( $service->budget );

                return $this->success( null, 'Serviço excluído com sucesso' );

            } );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao excluir serviço',
                null,
                $e,
            );
        }
    }

    /**
     * Verifica se um serviço pode ser deletado baseado em suas dependências.
     */
    private function canDeleteService( Service $service ): bool
    {
        // Não pode deletar se tiver faturas
        if ( $service->invoices()->count() > 0 ) {
            return false;
        }

        return true;
    }

    /**
     * Retorna dados estatísticos para o dashboard de serviços.
     *
     * @param int $tenantId ID do tenant
     * @return array
     */
    public function getDashboardData( int $tenantId ): array
    {
        try {
            // Buscar estatísticas dos serviços
            $statsResult = $this->getServiceStats( $tenantId );

            if ( !$statsResult->isSuccess() ) {
                return [
                    'total_services'      => 0,
                    'active_services'     => 0,
                    'completed_services'  => 0,
                    'cancelled_services'  => 0,
                    'total_service_value' => 0,
                    'status_breakdown'    => [],
                    'recent_services'     => collect()
                ];
            }

            $stats = $statsResult->getData();

            // Buscar serviços recentes (últimos 10)
            $recentServices = $this->getRecentServicesForDashboard( $tenantId );

            return [
                'total_services'      => $stats[ 'total' ],
                'active_services'     => $stats[ 'active' ],
                'completed_services'  => $stats[ 'completed' ],
                'cancelled_services'  => $stats[ 'cancelled' ],
                'total_service_value' => $stats[ 'total_value' ],
                'status_breakdown'    => $stats[ 'status_breakdown' ] ?? [],
                'recent_services'     => $recentServices
            ];

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao obter dados do dashboard de serviços', [
                'error'     => $e->getMessage(),
                'tenant_id' => $tenantId
            ] );

            return [
                'total_services'      => 0,
                'active_services'     => 0,
                'completed_services'  => 0,
                'cancelled_services'  => 0,
                'total_service_value' => 0,
                'status_breakdown'    => [],
                'recent_services'     => collect()
            ];
        }
    }

    /**
     * Retorna estatísticas básicas dos serviços para um tenant.
     */
    private function getServiceStats( int $tenantId ): ServiceResult
    {
        try {
            $services = Service::where( 'tenant_id', $tenantId )->get();

            $total      = $services->count();
            $active     = $services->filter( fn( $s ) => $s->status->isActive() )->count();
            $completed  = $services->filter( fn( $s ) => $s->status->value === ServiceStatus::COMPLETED->value )->count();
            $cancelled  = $services->filter( fn( $s ) => $s->status->value === ServiceStatus::CANCELLED->value )->count();
            $totalValue = $services->sum( 'total' );

            // Agrupar por status com cores
            $statusBreakdown = $services->groupBy( 'status' )->map( function ( $group, $status ) {
                $serviceStatus = ServiceStatus::fromString( $status );
                return [
                    'count' => $group->count(),
                    'color' => $serviceStatus?->getColor() ?? '#6c757d'
                ];
            } )->toArray();

            return $this->success( [
                'total'            => $total,
                'active'           => $active,
                'completed'        => $completed,
                'cancelled'        => $cancelled,
                'total_value'      => $totalValue,
                'status_breakdown' => $statusBreakdown
            ], 'Estatísticas calculadas' );

        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao calcular estatísticas',
                null,
                $e,
            );
        }
    }

    /**
     * Retorna serviços recentes para exibição no dashboard.
     */
    private function getRecentServicesForDashboard( int $tenantId ): \Illuminate\Support\Collection
    {
        return Service::where( 'tenant_id', $tenantId )
            ->with( [ 'budget.customer', 'category' ] )
            ->orderBy( 'created_at', 'desc' )
            ->limit( 10 )
            ->get();
    }

    /**
     * Cancela um serviço alterando o status para CANCELLED.
     */
    public function cancelService( string $code ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($code) {
                $service = Service::where( 'code', $code )->first();

                if ( !$service ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Serviço {$code} não encontrado",
                    );
                }

                // Verificar se pode ser cancelado
                if ( !$service->serviceStatus->canEdit() ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Serviço não pode ser cancelado no status atual',
                    );
                }

                // Atualizar status para CANCELLED
                $service->update( [ 'status' => ServiceStatus::CANCELLED->value ] );

                // Atualizar total do orçamento
                $this->updateBudgetTotal( $service->budget );

                return $this->success( $service->fresh(), 'Serviço cancelado com sucesso' );

            } );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao cancelar serviço',
                null,
                $e,
            );
        }
    }

    /**
     * Atualiza o status de um serviço usando token de confirmação.
     */
    public function updateStatusByToken( string $serviceCode, string $token, string $newStatus, ?string $reason = null ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($serviceCode, $token, $newStatus, $reason) {
                // Buscar serviço
                $service = Service::where( 'code', $serviceCode )->first();

                if ( !$service ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Serviço {$serviceCode} não encontrado",
                    );
                }

                // Verificar token de confirmação
                $confirmationToken = $service->userConfirmationToken;

                if ( !$confirmationToken || $confirmationToken->token !== $token ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Token de confirmação inválido',
                    );
                }

                // Verificar se token não expirou
                if ( $confirmationToken->expires_at->isPast() ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Token de confirmação expirado',
                    );
                }

                // Validar transição de status
                $allowedTransitions = ServiceStatus::getAllowedTransitions( $service->status );
                if ( !in_array( $newStatus, $allowedTransitions ) ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        "Transição de {$service->status} para {$newStatus} não permitida",
                    );
                }

                // Atualizar serviço
                $updateData = [ 'status' => $newStatus ];
                if ( $reason ) {
                    $updateData[ 'reason' ] = $reason;
                }

                $service->update( $updateData );

                // Atualizar orçamento em cascata se necessário
                $this->updateBudgetStatusIfNeeded( $service, $newStatus );

                // Remover token usado
                $confirmationToken->delete();

                return $this->success( $service->fresh(), 'Status atualizado com sucesso' );

            } );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao atualizar status do serviço',
                null,
                $e,
            );
        }
    }

}
