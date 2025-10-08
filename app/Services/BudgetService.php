<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Contracts\Interfaces\ActivatableInterface;
use App\Contracts\Interfaces\PaginatableInterface;
use App\Contracts\Interfaces\SlugableInterface;
use App\Models\Budget;
use App\Repositories\BudgetRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CustomerRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Services\NotificationService;
use App\Services\PdfService;
use App\Support\ServiceResult;
use App\Traits\SlugGenerator;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BudgetService extends BaseTenantService
{
    use SlugGenerator;

    private BudgetRepository     $budgetRepository;
    private CustomerRepository   $customerRepository;
    private CategoryRepository   $categoryRepository;
    private ?PdfService          $pdfService;
    private ?NotificationService $notificationService;
    private ActivityService      $activityService;

    public function __construct(
        BudgetRepository $budgetRepository,
        CustomerRepository $customerRepository,
        CategoryRepository $categoryRepository,
        ActivityService $activityService,
        ?PdfService $pdfService = null,
        ?NotificationService $notificationService = null,
    ) {
        $this->budgetRepository    = $budgetRepository;
        $this->customerRepository  = $customerRepository;
        $this->categoryRepository  = $categoryRepository;
        $this->activityService     = $activityService;
        $this->pdfService          = $pdfService;
        $this->notificationService = $notificationService;
    }

    protected function findEntityByIdAndTenantId( int $id, int $tenantId ): ?Model
    {
        return $this->budgetRepository->findByIdAndTenantId( $id, $tenantId );
    }

    protected function listEntitiesByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->budgetRepository->listByTenantId( $tenantId, $filters, $orderBy, $limit, $offset );
    }

    protected function createEntity( array $data, int $tenantId ): Model
    {
        $budget = new Budget();
        $budget->fill( [
            'tenant_id'   => $tenantId,
            'customer_id' => $data[ 'customer_id' ],
            'category_id' => $data[ 'category_id' ],
            'amount'      => $data[ 'amount' ],
            'status'      => $data[ 'status' ] ?? 'pending',
            'slug'        => $this->generateSlug( $data[ 'title' ] ?? 'Budget ' . time() ),
        ] );
        return $budget;
    }

    protected function updateEntity( Model $entity, array $data, int $tenantId ): void
    {
        $entity->fill( $data );
    }

    protected function saveEntity( Model $entity ): bool
    {
        return $entity->save();
    }

    protected function deleteEntity( Model $entity ): bool
    {
        // Deletar itens relacionados se necessário (assume cascade or manual)
        $entity->items()->delete();
        return $entity->delete();
    }

    protected function belongsToTenant( Model $entity, int $tenantId ): bool
    {
        return (int) $entity->tenant_id === $tenantId;
    }

    protected function canDeleteEntity( Model $entity ): bool
    {
        // Não deletar se status completed ou tem payments/invoices
        return $entity->status !== 'completed' && $entity->payments()->count() === 0 && $entity->invoices()->count() === 0;
    }

    /**
     * Busca budget por ID e tenant_id.
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        $budget = $this->findEntityByIdAndTenantId( $id, $tenant_id );
        if ( !$budget ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Budget não encontrado.' );
        }
        return $this->success( $budget, 'Budget encontrado.' );
    }

    /**
     * Lista budgets por tenant_id.
     */
    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult
    {
        $budgets = $this->listEntitiesByTenantId( $tenant_id, $filters );
        return $this->success( $budgets, 'Budgets listados.' );
    }

    /**
     * Lista budgets por tenant_id com opções avançadas.
     */
    public function listByTenantIdWithOptions( int $tenant_id, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        $budgets = $this->listEntitiesByTenantId( $tenant_id, $filters, $orderBy, $limit, $offset );
        return $this->success( $budgets, 'Budgets listados.' );
    }

    /**
     * Atualiza budget por ID e tenant_id.
     */
    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        $budget = $this->findEntityByIdAndTenantId( $id, $tenant_id );
        if ( !$budget ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Budget não encontrado.' );
        }

        $validation = $this->validateForTenant( $data, $tenant_id, true );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        $this->updateEntity( $budget, $data, $tenant_id );
        if ( !$this->saveEntity( $budget ) ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao atualizar budget.' );
        }

        return $this->success( $budget, 'Budget atualizado com sucesso.' );
    }

    /**
     * Deleta budget por ID e tenant_id.
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        $budget = $this->findEntityByIdAndTenantId( $id, $tenant_id );
        if ( !$budget ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Budget não encontrado.' );
        }

        if ( !$this->canDeleteEntity( $budget ) ) {
            return $this->error( OperationStatus::INVALID_DATA, 'Budget não pode ser deletado.' );
        }

        if ( !$this->deleteEntity( $budget ) ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao deletar budget.' );
        }

        return $this->success( null, 'Budget deletado com sucesso.' );
    }

    /**
     * Valida dados básicos (implementação da interface base).
     *
     * @param array $data Dados a validar
     * @param bool $isUpdate Se é atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        // Validação básica - requer tenant_id para operações tenant-aware
        if ( !isset( $data[ 'tenant_id' ] ) ) {
            return $this->error( OperationStatus::INVALID_DATA, 'tenant_id é obrigatório.' );
        }

        return $this->validateForTenant( $data, (int) $data[ 'tenant_id' ], $isUpdate );
    }

    /**
     * Valida dados para tenant (público para compatibilidade com interface base).
     *
     * @param array $data Dados a validar
     * @param int $tenant_id ID do tenant
     * @param bool $isUpdate Se é atualização
     * @return ServiceResult Resultado da validação
     */
    protected function validateForTenant( array $data, int $tenant_id, bool $isUpdate = false ): ServiceResult
    {
        $rules = [
            'customer_id' => [
                'required',
                Rule::exists( 'customers', 'id' )->where( fn( $q ) => $q->where( 'tenant_id', $tenant_id ) )
            ],
            'category_id' => [
                'required',
                Rule::exists( 'categories', 'id' )->where( fn( $q ) => $q->where( 'tenant_id', $tenant_id ) )
            ],
            'amount'      => 'required|numeric|min:0',
            'status'      => 'required|in:pending,approved,rejected,completed,finalized',
            'title'       => 'nullable|string|max:255',
        ];

        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }

        return $this->success();
    }

    /**
     * Cria budget com itens relacionados em transação.
     */
    public function createByTenantId( array $data, int $tenantId ): ServiceResult
    {
        return DB::transaction( function () use ($data, $tenantId) {
            $validation = $this->validateForTenant( $data, $tenantId );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            $budget = $this->createEntity( $data, $tenantId );
            if ( !$this->saveEntity( $budget ) ) {
                return $this->error( OperationStatus::ERROR, 'Falha ao salvar budget.' );
            }

            // Criar itens se presentes
            if ( isset( $data[ 'items' ] ) && is_array( $data[ 'items' ] ) ) {
                foreach ( $data[ 'items' ] as $itemData ) {
                    $budget->items()->create( [
                        'tenant_id'   => $tenantId,
                        'budget_id'   => $budget->id,
                        'description' => $itemData[ 'description' ],
                        'quantity'    => $itemData[ 'quantity' ],
                        'price'       => $itemData[ 'price' ],
                    ] );
                }
            }

            // Carregar relações
            $budget->load( [ 'customer', 'category', 'items' ] );

            return $this->success( $budget, 'Budget criado com sucesso.' );
        } );
    }

    /**
     * Paginação para tenant
     */
    public function paginateByTenantId( int $tenantId, int $page = 1, int $perPage = 15, array $filters = [], ?array $orderBy = null ): ServiceResult
    {
        $paginated = $this->budgetRepository->paginateByTenantId( $tenantId, $page, $perPage, $filters, $orderBy );
        return $this->success( $paginated, 'Paginação realizada com sucesso.' );
    }

    /**
     * Busca budget por slug e tenant.
     */
    public function getBySlugAndTenantId( string $slug, int $tenantId ): ServiceResult
    {
        $entity = $this->budgetRepository->findBySlugAndTenantId( $slug, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Budget não encontrado pelo slug.' );
        }
        return $this->success( $entity, 'Budget encontrado pelo slug.' );
    }

    /**
     * Lista budgets ativos para tenant.
     */
    public function listActiveByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $defaultFilters = [ 'status' => [ 'pending', 'approved' ] ];
        $filters        = array_merge( $defaultFilters, $filters );
        $entities       = $this->listEntitiesByTenantId( $tenantId, $filters, $orderBy, $limit, null );
        return $this->success( $entities, 'Budgets ativos listados.' );
    }

    /**
     * Contagem por tenant.
     */
    public function countByTenantId( int $tenantId, array $filters = [] ): ServiceResult
    {
        $count = $this->budgetRepository->countByTenantId( $tenantId, $filters );
        return $this->success( $count, 'Contagem realizada.' );
    }

    /**
     * Verifica existência por critérios e tenant.
     */
    public function existsByTenantId( array $criteria, int $tenantId ): ServiceResult
    {
        $exists = $this->budgetRepository->existsByTenantId( $criteria, $tenantId );
        return $this->success( $exists, $exists ? 'Existe' : 'Não existe' );
    }

    /**
     * Deleta múltiplos budgets por tenant.
     */
    public function deleteManyByTenantId( array $id, int $tenantId ): ServiceResult
    {
        $deleted = $this->budgetRepository->deleteManyByIdsAndTenantId( $id, $tenantId );
        return $this->success( $deleted, "{$deleted} budgets deletados." );
    }

    /**
     * Atualiza múltiplos budgets por tenant.
     */
    public function updateManyByTenantId( array $criteria, array $updates, int $tenantId ): ServiceResult
    {
        $updated = $this->budgetRepository->updateManyByTenantId( $criteria, $updates, $tenantId );
        return $this->success( $updated, "{$updated} budgets atualizados." );
    }

    /**
     * Busca por critérios e tenant.
     */
    public function findByAndTenantId( array $criteria, int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $entities = $this->budgetRepository->findByAndTenantId( $criteria, $tenantId, $orderBy, $limit, null );
        return $this->success( $entities, 'Encontrados por critérios.' );
    }

    /**
     * Valida se a transição de status é permitida.
     *
     * @param string $currentStatus Status atual do budget
     * @param string $newStatus Novo status desejado
     * @return bool True se a transição é válida
     */
    private function isValidStatusTransition( string $currentStatus, string $newStatus ): bool
    {
        $validTransitions = [
            'pending'   => [ 'approved', 'rejected' ],
            'approved'  => [ 'finalized', 'completed', 'rejected' ],
            'rejected'  => [ 'pending' ],
            'finalized' => [ 'completed' ],
            'completed' => []
        ];

        return in_array( $newStatus, $validTransitions[ $currentStatus ] ?? [], true );
    }

    /**
     * Gerencia mudanças de status do budget.
     */
    public function handleStatusChange( Budget $budget, string $newStatus, int $tenantId ): ServiceResult
    {
        if ( !$this->belongsToTenant( $budget, $tenantId ) ) {
            return $this->error( OperationStatus::UNAUTHORIZED, 'Budget não pertence ao tenant.' );
        }

        $currentStatus = $budget->status;
        if ( !$this->isValidStatusTransition( $currentStatus, $newStatus ) ) {
            return $this->error( OperationStatus::INVALID_DATA, "Transição inválida de {$currentStatus} para {$newStatus}." );
        }

        $budget->status = $newStatus;
        if ( !$budget->save() ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao atualizar status.' );
        }

        return $this->success( $budget, 'Status atualizado com sucesso.' );
    }

    /**
     * Prepara dados para visualização do budget.
     */
    public function getBudgetShowData( int $budgetId, int $tenantId ): ServiceResult
    {
        $budget = $this->budgetRepository->findByIdAndTenantId( $budgetId, $tenantId );
        if ( !$budget ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Budget não encontrado.' );
        }

        $budget->load( [ 'customer', 'category', 'items' ] );

        $totals = [
            'subtotal'    => $budget->items->sum( fn( $i ) => $i->quantity * $i->price ) ?? 0,
            'tax'         => $budget->tax_amount ?? 0,
            'total'       => $budget->amount,
            'items_count' => $budget->items->count()
        ];

        $showData = [
            'budget' => $budget,
            'totals' => $totals
        ];

        return $this->success( $showData, 'Dados preparados.' );
    }

    /**
     * Prepara dados para impressão do budget.
     */
    public function getBudgetPrintData( int $budgetId, int $tenantId ): ServiceResult
    {
        $budget = $this->budgetRepository->findByIdAndTenantId( $budgetId, $tenantId );
        if ( !$budget ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Budget não encontrado.' );
        }

        $budget->load( [ 'customer', 'category', 'items' ] );

        $printData = [
            'budget' => [
                'id'       => $budget->id,
                'title'    => $budget->title ?? 'Orçamento #' . $budget->id,
                'status'   => ucfirst( $budget->status ),
                'customer' => $budget->customer->name ?? 'Cliente não identificado',
                'category' => $budget->category->name ?? 'Categoria não definida'
            ],
            'items'  => $budget->items->map( function ( $item ) {
                return [
                    'description' => $item->description,
                    'quantity'    => (float) $item->quantity,
                    'price'       => number_format( (float) $item->price, 2, ',', '.' ),
                    'total'       => number_format( (float) ( $item->quantity * $item->price ), 2, ',', '.' )
                ];
            } )->toArray(),
            'totals' => [
                'subtotal' => number_format( $budget->items->sum( fn( $i ) => $i->quantity * $i->price ) ?? 0, 2, ',', '.' ),
                'total'    => number_format( (float) $budget->amount, 2, ',', '.' ),
                'currency' => 'R$'
            ]
        ];

        return $this->success( $printData, 'Dados preparados.' );
    }

    /**
     * Cria budget com código único e logging de atividade.
     * Baseado no padrão do sistema antigo.
     */
    public function createBudgetWithCode( array $data, int $tenantId, int $userId ): ServiceResult
    {
        return DB::transaction( function () use ($data, $tenantId, $userId) {
            try {
                // Validação
                $validation = $this->validateForTenant( $data, $tenantId );
                if ( !$validation->isSuccess() ) {
                    return $validation;
                }

                // Gerar código único do orçamento
                $budgetCode        = $this->generateBudgetCode( $tenantId );
                $data[ 'code' ]    = $budgetCode;
                $data[ 'user_id' ] = $userId;

                // Criar budget
                $budget = $this->createEntity( $data, $tenantId );
                if ( !$this->saveEntity( $budget ) ) {
                    return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar budget.' );
                }

                // Criar itens se presentes
                if ( isset( $data[ 'items' ] ) && is_array( $data[ 'items' ] ) ) {
                    $this->createBudgetItems( $budget, $data[ 'items' ], $tenantId );
                }

                // Calcular totais
                $this->calculateBudgetTotals( $budget );

                // Log da atividade
                $this->logBudgetActivity( 'budget_created', $budget, $userId, [
                    'budget_code' => $budgetCode,
                    'customer_id' => $budget->customer_id,
                    'amount'      => $budget->amount
                ] );

                // Enviar notificação se configurado
                if ( $this->notificationService ) {
                    $this->notificationService->sendBudgetCreatedNotification( $budget );
                }

                // Carregar relações
                $budget->load( [ 'customer', 'category', 'items', 'user' ] );

                return ServiceResult::success( $budget, 'Orçamento criado com sucesso.' );

            } catch ( Exception $e ) {
                Log::error( 'Erro ao criar orçamento', [
                    'tenant_id' => $tenantId,
                    'user_id'   => $userId,
                    'error'     => $e->getMessage(),
                    'trace'     => $e->getTraceAsString()
                ] );

                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Erro interno ao criar orçamento: ' . $e->getMessage()
                );
            }
        } );
    }

    /**
     * Gera código único para o orçamento baseado no tenant.
     */
    private function generateBudgetCode( int $tenantId ): string
    {
        $prefix = 'ORC';
        $year   = date( 'Y' );
        $month  = date( 'm' );

        // Buscar último número sequencial do mês
        $lastBudget = $this->budgetRepository->getLastBudgetByTenantAndMonth( $tenantId, $year, $month );
        $sequence   = $lastBudget ? (int) substr( $lastBudget->code, -4 ) + 1 : 1;

        return sprintf( '%s%s%s%04d', $prefix, $year, $month, $sequence );
    }

    /**
     * Cria itens do orçamento.
     */
    private function createBudgetItems( Budget $budget, array $items, int $tenantId ): void
    {
        foreach ( $items as $itemData ) {
            $budget->items()->create( [
                'tenant_id'   => $tenantId,
                'budget_id'   => $budget->id,
                'description' => $itemData[ 'description' ],
                'quantity'    => $itemData[ 'quantity' ],
                'price'       => $itemData[ 'price' ],
                'total'       => $itemData[ 'quantity' ] * $itemData[ 'price' ],
            ] );
        }
    }

    /**
     * Calcula totais do orçamento.
     */
    private function calculateBudgetTotals( Budget $budget ): void
    {
        $subtotal = $budget->items()->sum( DB::raw( 'quantity * price' ) );
        $discount = $budget->discount_percentage ? ( $subtotal * $budget->discount_percentage / 100 ) : 0;
        $total    = $subtotal - $discount;

        $budget->update( [
            'subtotal'        => $subtotal,
            'discount_amount' => $discount,
            'total'           => $total,
        ] );
    }

    /**
     * Log de atividade do orçamento.
     */
    private function logBudgetActivity( string $action, Budget $budget, int $userId, array $details = [] ): void
    {
        $this->activityService->create( [
            'tenant_id'   => $budget->tenant_id,
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => 'Budget',
            'entity_id'   => $budget->id,
            'details'     => array_merge( [
                'budget_code' => $budget->code,
                'status'      => $budget->status,
            ], $details ),
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ] );
    }

    /**
     * Atualiza status do orçamento com validações e logging.
     */
    public function updateBudgetStatus( int $budgetId, string $newStatus, int $tenantId, int $userId, ?string $reason = null ): ServiceResult
    {
        return DB::transaction( function () use ($budgetId, $newStatus, $tenantId, $userId, $reason) {
            $budget = $this->findEntityByIdAndTenantId( $budgetId, $tenantId );
            if ( !$budget ) {
                return ServiceResult::notFound( 'Orçamento não encontrado.' );
            }

            // Validar transição de status
            if ( !$this->isValidStatusTransition( $budget->status, $newStatus ) ) {
                return ServiceResult::invalidData(
                    "Transição de status inválida: {$budget->status} -> {$newStatus}",
                );
            }

            $oldStatus                 = $budget->status;
            $budget->status            = $newStatus;
            $budget->status_updated_at = now();
            $budget->status_updated_by = $userId;

            if ( !$this->saveEntity( $budget ) ) {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar status.' );
            }

            // Log da atividade
            $this->logBudgetActivity( 'status_changed', $budget, $userId, [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason'     => $reason,
            ] );

            // Notificação de mudança de status
            if ( $this->notificationService ) {
                $this->notificationService->sendBudgetStatusChangedNotification( $budget, $oldStatus );
            }

            return ServiceResult::success( $budget, 'Status atualizado com sucesso.' );
        } );
    }

    /**
     * Busca orçamentos com filtros avançados baseados no sistema antigo.
     */
    public function getBudgetFullById( int $budgetId, int $tenantId ): ServiceResult
    {
        try {
            $budget = $this->budgetRepository->getBudgetWithFullDetails( $budgetId, $tenantId );

            if ( !$budget ) {
                return ServiceResult::notFound( 'Orçamento não encontrado.' );
            }

            return ServiceResult::success( $budget, 'Orçamento recuperado com detalhes completos.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao buscar orçamento completo', [
                'budget_id' => $budgetId,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao recuperar orçamento: ' . $e->getMessage()
            );
        }
    }

    /**
     * Duplica orçamento existente.
     */
    public function duplicateBudget( int $budgetId, int $tenantId, int $userId, array $overrides = [] ): ServiceResult
    {
        return DB::transaction( function () use ($budgetId, $tenantId, $userId, $overrides) {
            $originalBudget = $this->findEntityByIdAndTenantId( $budgetId, $tenantId );
            if ( !$originalBudget ) {
                return ServiceResult::notFound( 'Orçamento original não encontrado.' );
            }

            // Preparar dados para duplicação
            $budgetData = array_merge( [
                'customer_id'         => $originalBudget->customer_id,
                'category_id'         => $originalBudget->category_id,
                'title'               => 'Cópia de ' . $originalBudget->title,
                'description'         => $originalBudget->description,
                'discount_percentage' => $originalBudget->discount_percentage,
                'status'              => 'pending',
            ], $overrides );

            // Duplicar itens
            $items = $originalBudget->items->map( function ( $item ) {
                return [
                    'description' => $item->description,
                    'quantity'    => $item->quantity,
                    'price'       => $item->price,
                ];
            } )->toArray();

            $budgetData[ 'items' ] = $items;

            // Criar novo orçamento
            $result = $this->createBudgetWithCode( $budgetData, $tenantId, $userId );

            if ( $result->isSuccess() ) {
                // Log da duplicação
                $this->logBudgetActivity( 'budget_duplicated', $result->getData(), $userId, [
                    'original_budget_id' => $budgetId,
                    'original_code'      => $originalBudget->code,
                ] );
            }

            return $result;
        } );
    }

}
