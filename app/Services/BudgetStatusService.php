<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Models\BudgetStatus;
use App\Repositories\BudgetStatusRepository;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BudgetStatusService extends BaseNoTenantService
{
    private BudgetStatusRepository $budgetStatusRepository;

    public function __construct( BudgetStatusRepository $budgetStatusRepository )
    {
        parent::__construct();
        $this->budgetStatusRepository = $budgetStatusRepository;
    }

    /**
     * Retorna a classe do modelo BudgetStatus.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getModelClass(): \Illuminate\Database\Eloquent\Model
    {
        return new \App\Models\BudgetStatus();
    }

    protected function findEntityById( int $id ): ?Model
    {
        return $this->budgetStatusRepository->findById( $id );
    }

    protected function listEntities( ?array $orderBy = null, ?int $limit = null ): array
    {
        $filters = [];
        if ( $orderBy !== null ) {
            $filters[ 'order' ] = $orderBy;
        }
        if ( $limit !== null ) {
            $filters[ 'limit' ] = $limit;
        }
        return $this->budgetStatusRepository->findAll( $filters );
    }

    protected function createEntity( array $data ): Model
    {
        $budgetStatus = new BudgetStatus();
        $budgetStatus->fill( $data );
        return $budgetStatus;
    }

    protected function updateEntity( int $id, array $data ): Model
    {
        $budgetStatus = $this->findEntityById( $id );
        if ( !$budgetStatus ) {
            throw new \Exception( 'BudgetStatus not found' );
        }
        $budgetStatus->fill( $data );
        return $budgetStatus;
    }

    protected function deleteEntity( int $id ): bool
    {
        $budgetStatus = $this->findEntityById( $id );
        if ( !$budgetStatus ) {
            return false;
        }
        return $budgetStatus->delete();
    }

    protected function canDeleteEntity( int $id ): bool
    {
        // BudgetStatus can be deleted if not referenced by any budget
        return true; // For now, allow deletion - can be enhanced with business logic
    }

    protected function validateForGlobal( array $data, bool $isUpdate = false ): ServiceResult
    {
        $id = $isUpdate ? ( $data[ 'id' ] ?? null ) : null;

        $rules = [ 
            'name'        => 'required|string|max:255',
            'slug'        => [ 
                'required',
                'string',
                'max:255',
                Rule::unique( 'budget_statuses', 'slug' )->ignore( $id ),
            ],
            'description' => 'nullable|string|max:1000',
            'color'       => 'nullable|string|max:7',
            'active'      => 'boolean',
            'order_index' => 'integer|min:0',
        ];

        $validator = Validator::make( $data, $rules );

        if ( $validator->fails() ) {
            return $this->error( OperationStatus::INVALID_DATA, 'Validation failed.', $validator->errors()->toArray() );
        }

        return $this->success( $data );
    }

    /**
     * Validação para tenant (não aplicável para serviços NoTenant).
     *
     * Este método é obrigatório por herança mas não realiza validação específica
     * de tenant, pois esta é uma classe NoTenant.
     *
     * @param array $data Dados a validar
     * @param int $tenant_id ID do tenant
     * @param bool $is_update Se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    protected function validateForTenant( array $data, int $tenant_id, bool $is_update = false ): ServiceResult
    {
        // Para serviços NoTenant, não há validação específica de tenant
        // Retorna sucesso pois a validação é feita pelo método validateForGlobal
        return $this->success();
    }

    /**
     * List active budget statuses, ordered by order_index ascending.
     *
     * @param array $orderBy Custom order, default ['order_index', 'asc']
     * @return ServiceResult
     */
    public function listActive( array $orderBy = [ 'order_index', 'asc' ] ): ServiceResult
    {
        $data = $this->budgetStatusRepository->findActive( $orderBy );
        return $this->success( $data, 'Status ativos listados com sucesso.' );
    }

    /**
     * Get budget status by slug.
     *
     * @param string $slug
     * @return ServiceResult
     */
    public function getBySlug( string $slug ): ServiceResult
    {
        $data = $this->budgetStatusRepository->findBySlug( $slug );
        if ( !$data ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Status não encontrado.' );
        }
        return $this->success( $data, 'Status encontrado com sucesso.' );
    }

    /**
     * List budget statuses ordered by order_index ascending.
     *
     * @return ServiceResult
     */
    public function listOrderedByIndex(): ServiceResult
    {
        $data = $this->budgetStatusRepository->findOrderedBy( 'order_index', 'asc' );
        return $this->success( $data, 'Status ordenados por índice listados com sucesso.' );
    }

}
