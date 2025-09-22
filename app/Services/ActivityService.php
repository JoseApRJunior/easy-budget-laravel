<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ActivityService extends BaseTenantService
{
    protected function findEntityByIdAndTenantId( int $id, int $tenantId ): ?Model
    {
        // Assume Activity model exists
        return \App\Models\Activity::where( 'tenant_id', $tenantId )->find( $id );
    }

    protected function listEntitiesByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        $query = \App\Models\Activity::where( 'tenant_id', $tenantId );

        // Handle filters
        if ( isset( $filters[ 'order' ] ) ) {
            $orderBy = $filters[ 'order' ];
            $query->orderBy( $orderBy[ 0 ] ?? 'id', $orderBy[ 1 ] ?? 'asc' );
        }

        if ( isset( $filters[ 'limit' ] ) ) {
            $query->limit( $filters[ 'limit' ] );
        }

        return $query->get()->all();
    }

    protected function createEntity( array $data, int $tenantId ): Model
    {
        $data[ 'tenant_id' ] = $tenantId;
        $activity            = new \App\Models\Activity();
        $activity->fill( $data );
        $this->saveEntity( $activity );
        return $activity;
    }

    protected function updateEntity( Model $entity, array $data, int $tenantId ): void
    {
        $entity->fill( $data );
        $this->saveEntity( $entity );
    }

    protected function deleteEntity( Model $entity ): bool
    {
        return $entity->delete();
    }

    protected function canDeleteEntity( Model $entity ): bool
    {
        // Lógica para verificar se pode deletar (ex: não é recente)
        return true;
    }

    protected function saveEntity( Model $entity ): bool
    {
        return $entity->save();
    }

    protected function belongsToTenant( Model $entity, int $tenantId ): bool
    {
        return $entity->tenant_id === $tenantId;
    }

    public function validateForTenant( array $data, int $tenantId, bool $isUpdate = false ): ServiceResult
    {
        $rules     = [ 
            'action'   => 'required|string',
            'metadata' => 'nullable|array',
            'user_id'  => 'nullable|integer|exists:users,id',
        ];
        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }
        return $this->success( $data );
    }

    /**
     * Busca uma atividade pelo ID e tenant_id.
     *
     * @param int $id ID da atividade
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function getByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        $entity = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Atividade não encontrada.' );
        }
        return $this->success( $entity );
    }

    /**
     * Lista atividades por tenant_id com filtros.
     *
     * @param int $tenant_id ID do tenant
     * @param array $filters Filtros opcionais
     * @param ?array $orderBy Ordem dos resultados
     * @param ?int $limit Limite de resultados
     * @param ?int $offset Offset dos resultados
     * @return ServiceResult
     */
    public function listByTenantId( int $tenant_id, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        $data = $this->listEntitiesByTenantId( $tenant_id, $filters, $orderBy, $limit, $offset );
        return $this->success( $data );
    }

    /**
     * Valida os dados de entrada para operações de criação ou atualização.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        // Extract tenant_id from data if available, otherwise use 0 as default
        $tenantId = $data[ 'tenant_id' ] ?? 0;

        // Delegate to validateForTenant method
        return $this->validateForTenant( $data, $tenantId, $isUpdate );
    }

    /**
     * Registra atividade global.
     */
    public function logActivity( string $action, array $data = [], int $tenantId ): ServiceResult
    {
        $activityData = [ 
            'action'     => $action,
            'metadata'   => $data,
            'user_id'    => Auth::id(),
            'created_at' => Carbon::now()->toDateTimeString(),
        ];
        return $this->createByTenantId( $activityData, $tenantId );
    }

    /**
     * List all activities with optional ordering and limit via filters.
     */
    public function listAll( array $filters = [], int $tenantId ): ServiceResult
    {
        $data = $this->listEntitiesByTenantId( $tenantId, $filters );
        return $this->success( $data, 'Atividades listadas com sucesso.' );
    }

    /**
     * Cria uma nova atividade para o tenant.
     *
     * @param array $data Dados da atividade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        $validation = $this->validate( $data );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        $data[ 'tenant_id' ] = $tenant_id;
        $entity              = $this->createEntity( $data, $tenant_id );
        return $this->success( $entity, 'Atividade criada com sucesso.' );
    }

    /**
     * Atualiza uma atividade existente.
     *
     * @param int $id ID da atividade
     * @param array $data Dados para atualização
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        $validation = $this->validate( $data, true );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        $entity = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Atividade não encontrada.' );
        }

        $this->updateEntity( $entity, $data, $tenantId );
        return $this->success( $entity, 'Atividade atualizada com sucesso.' );
    }

    /**
     * Remove uma atividade.
     *
     * @param int $id ID da atividade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        $entity = $this->findEntityByIdAndTenantId( $id, $tenant_id );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Atividade não encontrada.' );
        }

        if ( !$this->canDeleteEntity( $entity ) ) {
            return $this->error( OperationStatus::NOT_SUPPORTED, 'Atividade não pode ser removida.' );
        }

        $this->deleteEntity( $entity );
        return $this->success( null, 'Atividade removida com sucesso.' );
    }

}
