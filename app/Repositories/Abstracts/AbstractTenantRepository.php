<?php

declare(strict_types=1);

namespace App\Repositories\Abstracts;

use App\Contracts\Interfaces\TenantRepositoryInterface;
use App\Models\Traits\TenantScoped;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Repositório abstrato base para operações com modelos tenant-scoped
 *
 * Esta classe implementa todos os métodos básicos para repositórios que trabalham
 * com dados isolados por tenant, usando a nova estrutura unificada de contratos
 */
abstract class AbstractTenantRepository implements TenantRepositoryInterface
{
    /**
     * Classe do modelo Eloquent associado a este repositório
     */
    protected string $modelClass;

    /**
     * Instância do modelo Eloquent
     */
    protected Model $model;

    /**
     * Query builder instance
     */
    protected Builder $query;

    /**
     * Flag para controlar reset automático após operações
     */
    protected bool $resetAfterOperation = true;

    /**
     * Construtor do repositório
     */
    public function __construct()
    {
        if ( !isset( $this->modelClass ) ) {
            throw new Exception( 'A propriedade $modelClass deve ser definida na classe ' . static::class);
        }

        $this->model = new $this->modelClass();

        if ( !$this->model instanceof Model ) {
            throw new Exception( "A classe {$this->modelClass} deve estender Illuminate\Database\Eloquent\Model" );
        }

        $this->reset();
    }

    // ========== MÉTODOS DA INTERFACE BaseRepositoryInterface ==========

    /**
     * {@inheritdoc}
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * {@inheritdoc}
     */
    public function newModel( array $attributes = [] ): Model
    {
        return new $this->modelClass( $attributes );
    }

    /**
     * {@inheritdoc}
     */
    public function newQuery(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function count( array $filters = [] ): int
    {
        try {
            $query = $this->newQuery();
            if ( !empty( $filters ) ) {
                $query = $this->applyCriteria( $query, $filters );
            }
            return $query->count();
        } catch ( Throwable $e ) {
            $this->logError( 'count', $e, [ 'filters' => $filters ] );
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(): bool
    {
        try {
            return $this->newQuery()->exists();
        } catch ( Throwable $e ) {
            $this->logError( 'exists', $e );
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function truncate(): bool
    {
        try {
            DB::statement( 'SET FOREIGN_KEY_CHECKS=0;' );
            $this->model->truncate();
            DB::statement( 'SET FOREIGN_KEY_CHECKS=1;' );
            return true;
        } catch ( Throwable $e ) {
            $this->logError( 'truncate', $e );
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function find( int|string $id ): ?Model
    {
        $result = $this->query->find( $id );
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function first(): ?Model
    {
        $result = $this->query->first();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function last(): ?Model
    {
        $result = $this->query->orderBy( $this->model->getKeyName(), 'desc' )->first();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Collection
    {
        $result = $this->query->get();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function paginate( int $perPage = 15, array $filters = [] ): LengthAwarePaginator
    {
        $query = $this->newQuery();
        if ( !empty( $filters ) ) {
            $query = $this->applyCriteria( $query, $filters );
        }

        $result = $query->paginate( $perPage );
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy( array $criteria ): Collection
    {
        $this->applyCriteria( $this->query, $criteria );
        $result = $this->query->get();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function create( array $data ): Model
    {
        $this->validateTenantData( $data );
        return $this->model->create( $data );
    }

    /**
     * {@inheritdoc}
     */
    public function update( int $id, array $data ): bool
    {
        $model = $this->find( $id );
        if ( !$model ) {
            return false;
        }

        $this->validateTenantAccess( $model );
        $model->update( $data );
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete( int $id ): bool
    {
        $model = $this->find( $id );
        if ( !$model ) {
            return false;
        }

        $this->validateTenantAccess( $model );
        return $model->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function refresh( Model $entity ): ?Model
    {
        try {
            if ( $entity instanceof Model ) {
                $entity->refresh();
                return $entity;
            }
            return null;
        } catch ( Throwable $e ) {
            $this->logError( 'refresh', $e, [ 'entity_id' => $entity->getKey() ] );
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function transaction( callable $callback ): mixed
    {
        return DB::transaction( $callback );
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        DB::commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): void
    {
        DB::rollback();
    }

    // ========== MÉTODOS ESPECÍFICOS DA INTERFACE TenantRepositoryInterface ==========

    /**
     * {@inheritdoc}
     */
    public function findByTenantId( int $tenantId ): Collection
    {
        return $this->findByTenantIdWithFilters( $tenantId );
    }

    /**
     * {@inheritdoc}
     */
    public function findByIdAndTenantId( int $id, int $tenantId ): ?Model
    {
        try {
            $query  = $this->newQuery();
            $entity = $this->applyTenantFilter( $query, $tenantId )->find( $id );

            $this->logOperation( 'findByIdAndTenantId', [
                'id'        => $id,
                'tenant_id' => $tenantId,
                'found'     => $entity !== null
            ] );

            return $entity;
        } catch ( Throwable $e ) {
            $this->logError( 'findByIdAndTenantId', $e, [ 'id' => $id, 'tenant_id' => $tenantId ] );
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByTenantIdWithFilters( int $tenantId, array $filters = [] ): Collection
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $filters );

            $entities = $query->get();

            $this->logOperation( 'findByTenantIdWithFilters', [
                'tenant_id'   => $tenantId,
                'filters'     => $filters,
                'found_count' => $entities->count()
            ] );

            $this->resetIfNeeded();
            return $entities;
        } catch ( Throwable $e ) {
            $this->logError( 'findByTenantIdWithFilters', $e, [
                'tenant_id' => $tenantId,
                'filters'   => $filters
            ] );
            return collect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countByTenantId( int $tenantId, array $filters = [] ): int
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $filters );

            return $query->count();
        } catch ( Throwable $e ) {
            $this->logError( 'countByTenantId', $e, [
                'tenant_id' => $tenantId,
                'filters'   => $filters
            ] );
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createForTenant( array $data, int $tenantId ): Model
    {
        $data[ 'tenant_id' ] = $tenantId;
        return $this->create( $data );
    }

    /**
     * {@inheritdoc}
     */
    public function updateForTenant( int $id, array $data, int $tenantId ): bool
    {
        $model = $this->findByIdAndTenantId( $id, $tenantId );
        if ( !$model ) {
            return false;
        }

        $model->update( $data );
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteForTenant( int $id, int $tenantId ): bool
    {
        $model = $this->findByIdAndTenantId( $id, $tenantId );
        if ( !$model ) {
            return false;
        }

        return $model->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function paginateByTenant( int $tenantId, int $perPage = 15, array $filters = [] ): LengthAwarePaginator
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $filters );

            return $query->paginate( $perPage );
        } catch ( Throwable $e ) {
            $this->logError( 'paginateByTenant', $e, [
                'tenant_id' => $tenantId,
                'per_page'  => $perPage,
                'filters'   => $filters
            ] );

            return new LengthAwarePaginator( [], 0, $perPage );
        }
    }

    // ========== MÉTODOS AUXILIARES INTERNOS ==========

    /**
     * Aplica o filtro de tenant_id na query
     */
    protected function applyTenantFilter( Builder $query, int $tenantId ): Builder
    {
        return $query->where( 'tenant_id', $tenantId );
    }

    /**
     * Aplica critérios de busca na query
     */
    protected function applyCriteria( Builder $query, array $criteria ): Builder
    {
        foreach ( $criteria as $field => $value ) {
            if ( is_array( $value ) ) {
                $query->whereIn( $field, $value );
            } else {
                $query->where( $field, $value );
            }
        }

        return $query;
    }

    /**
     * Valida acesso do tenant ao modelo
     */
    protected function validateTenantAccess( Model $model ): void
    {
        if ( !$this->modelHasTenantScope() ) {
            return;
        }

        $currentTenantId = $this->getCurrentTenantId();
        $modelTenantId   = $model->getAttribute( 'tenant_id' );

        if ( $currentTenantId !== $modelTenantId ) {
            throw new Exception( 'Acesso negado: recurso não pertence ao tenant atual' );
        }
    }

    /**
     * Valida dados do tenant
     */
    protected function validateTenantData( array $data ): void
    {
        if ( !$this->modelHasTenantScope() ) {
            return;
        }

        $currentTenantId = $this->getCurrentTenantId();

        if ( isset( $data[ 'tenant_id' ] ) && $data[ 'tenant_id' ] !== $currentTenantId ) {
            throw new Exception( 'Tenant ID nos dados não corresponde ao tenant atual' );
        }
    }

    /**
     * Verifica se o modelo tem escopo de tenant
     */
    protected function modelHasTenantScope(): bool
    {
        return in_array( TenantScoped::class, class_uses_recursive( $this->model ) );
    }

    /**
     * Obtém o ID do tenant atual
     */
    protected function getCurrentTenantId(): ?int
    {
        return auth()->user()?->tenant_id ?? session( 'tenant_id' );
    }

    /**
     * Reseta a query se necessário
     */
    protected function resetIfNeeded(): void
    {
        if ( $this->resetAfterOperation ) {
            $this->reset();
        }
    }

    /**
     * Reseta os filtros aplicados
     */
    protected function reset(): self
    {
        $this->query = $this->model->newQuery();
        return $this;
    }

    /**
     * Registra uma operação no log
     */
    protected function logOperation( string $operation, array $context = [] ): void
    {
        Log::info( "Tenant Repository operation: {$operation}", array_merge( [
            'repository' => static::class,
            'model'      => $this->getModelClass()
        ], $context ) );
    }

    /**
     * Registra um erro no log
     */
    protected function logError( string $operation, Throwable $exception, array $context = [] ): void
    {
        Log::error( "Tenant Repository error in {$operation}: " . $exception->getMessage(), array_merge( [
            'repository' => static::class,
            'model'      => $this->getModelClass(),
            'exception'  => $exception
        ], $context ) );
    }

}
