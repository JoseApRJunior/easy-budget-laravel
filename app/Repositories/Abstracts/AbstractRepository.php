<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Interfaces\RepositoryInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Repositório abstrato base para operações tenant-aware usando Eloquent ORM
 *
 * Esta classe implementa todos os métodos básicos para repositórios que trabalham
 * com dados isolados por tenant, fornecendo funcionalidade completa de CRUD
 * com isolamento automático de dados por tenant_id
 */
abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * Classe do modelo Eloquent associado a este repositório
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * Instância do modelo Eloquent
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Construtor do repositório
     *
     * @throws Exception Se a classe do modelo não for definida
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
    }

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
     * Aplica o filtro de tenant_id na query
     *
     * @param Builder $query Query builder
     * @param int $tenantId ID do tenant
     * @return Builder Query builder com filtro de tenant aplicado
     */
    protected function applyTenantFilter( Builder $query, int $tenantId ): Builder
    {
        return $query->where( 'tenant_id', $tenantId );
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
    public function findManyByIdsAndTenantId( array $id, int $tenantId ): array
    {
        try {
            $query    = $this->newQuery();
            $entities = $this->applyTenantFilter( $query, $tenantId )->whereIn( 'id', $id )->get();

            $this->logOperation( 'findManyByIdsAndTenantId', [
                'ids_count'   => count( $id ),
                'tenant_id'   => $tenantId,
                'found_count' => $entities->count()
            ] );

            return $entities->all();
        } catch ( Throwable $e ) {
            $this->logError( 'findManyByIdsAndTenantId', $e, [ 'ids' => $id, 'tenant_id' => $tenantId ] );
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByAndTenantId(
        array $criteria,
        int $tenantId,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $criteria );
            $query = $this->applyOrderBy( $query, $orderBy );

            if ( $limit !== null ) {
                $query->limit( $limit );
            }

            if ( $offset !== null ) {
                $query->offset( $offset );
            }

            $entities = $query->get();

            $this->logOperation( 'findByAndTenantId', [
                'criteria'    => $criteria,
                'tenant_id'   => $tenantId,
                'found_count' => $entities->count()
            ] );

            return $entities->all();
        } catch ( Throwable $e ) {
            $this->logError( 'findByAndTenantId', $e, [
                'criteria'  => $criteria,
                'tenant_id' => $tenantId
            ] );
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByAndTenantId( array $criteria, int $tenantId ): ?Model
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $criteria );

            $entity = $query->first();

            $this->logOperation( 'findOneByAndTenantId', [
                'criteria'  => $criteria,
                'tenant_id' => $tenantId,
                'found'     => $entity !== null
            ] );

            return $entity;
        } catch ( Throwable $e ) {
            $this->logError( 'findOneByAndTenantId', $e, [
                'criteria'  => $criteria,
                'tenant_id' => $tenantId
            ] );
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findAllByTenantId(
        int $tenantId,
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $criteria );
            $query = $this->applyOrderBy( $query, $orderBy );

            if ( $limit !== null ) {
                $query->limit( $limit );
            }

            if ( $offset !== null ) {
                $query->offset( $offset );
            }

            $entities = $query->get();

            $this->logOperation( 'findAllByTenantId', [
                'criteria'    => $criteria,
                'tenant_id'   => $tenantId,
                'found_count' => $entities->count()
            ] );

            return $entities->all();
        } catch ( Throwable $e ) {
            $this->logError( 'findAllByTenantId', $e, [ 'tenant_id' => $tenantId, 'criteria' => $criteria ] );
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlugAndTenantId( string $slug, int $tenantId ): ?Model
    {
        return $this->findOneByAndTenantId( [ 'slug' => $slug ], $tenantId );
    }

    /**
     * {@inheritdoc}
     */
    public function save( Model $entity, $tenantId ): Model|false
    {
        try {
            if ( !( $entity instanceof Model ) ) {
                throw new Exception( 'Entity must be an instance of Illuminate\Database\Eloquent\Model' );
            }

            // Garante que o tenant_id está definido
            if ( array_key_exists( 'tenant_id', $entity->getAttributes() ) || $entity->isFillable( 'tenant_id' ) ) {
                $entity->setAttribute( 'tenant_id', $tenantId );
            }

            $saved = $entity->save();

            if ( !$saved ) {
                $this->logError( 'save', new Exception( 'Save operation failed' ), [
                    'entity_id' => $entity->getKey(),
                    'tenant_id' => $tenantId
                ] );
                return false;
            }

            $this->logOperation( 'save', [
                'entity_id' => $entity->getKey(),
                'tenant_id' => $tenantId,
                'success'   => true
            ] );

            return $entity;
        } catch ( Throwable $e ) {
            $this->logError( 'save', $e, [
                'entity_id' => $entity->getKey(),
                'tenant_id' => $tenantId
            ] );
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByIdAndTenantId( int $id, int $tenantId ): bool
    {
        try {
            $query   = $this->newQuery();
            $deleted = $this->applyTenantFilter( $query, $tenantId )->where( 'id', $id )->delete();

            $success = $deleted > 0;

            $this->logOperation( 'deleteByIdAndTenantId', [
                'id'        => $id,
                'tenant_id' => $tenantId,
                'success'   => $success
            ] );

            return $success;
        } catch ( Throwable $e ) {
            $this->logError( 'deleteByIdAndTenantId', $e, [
                'id'        => $id,
                'tenant_id' => $tenantId
            ] );
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete( int $id ): bool
    {
        try {
            $entity = $this->find( $id );
            if ( !$entity ) {
                return false;
            }

            return $entity->delete();
        } catch ( Throwable $e ) {
            $this->logError( 'delete', $e, [ 'id' => $id ] );
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countByTenantId( int $tenantId, array $criteria = [] ): int
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $criteria );

            return $query->count();
        } catch ( Throwable $e ) {
            $this->logError( 'countByTenantId', $e, [
                'tenant_id' => $tenantId,
                'criteria'  => $criteria
            ] );
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function existsByTenantId( array $criteria, int $tenantId ): bool
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $criteria );

            return $query->exists();
        } catch ( Throwable $e ) {
            $this->logError( 'existsByTenantId', $e, [
                'criteria'  => $criteria,
                'tenant_id' => $tenantId
            ] );
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function paginateByTenantId(
        int $tenantId,
        int $page = 1,
        int $perPage = 15,
        array $criteria = [],
        ?array $orderBy = null,
    ): array {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $criteria );
            $query = $this->applyOrderBy( $query, $orderBy );

            $paginatorResult = $query->paginate( $perPage, [ '*' ], 'page', $page );

            return [
                'data'         => $paginatorResult->items(),
                'total'        => $paginatorResult->total(),
                'current_page' => $paginatorResult->currentPage(),
                'per_page'     => $paginatorResult->perPage(),
                'last_page'    => $paginatorResult->lastPage(),
                'from'         => $paginatorResult->firstItem(),
                'to'           => $paginatorResult->lastItem()
            ];
        } catch ( Throwable $e ) {
            $this->logError( 'paginateByTenantId', $e, [
                'tenant_id' => $tenantId,
                'page'      => $page,
                'per_page'  => $perPage
            ] );

            return [
                'data'         => [],
                'total'        => 0,
                'current_page' => 1,
                'per_page'     => $perPage,
                'last_page'    => 1,
                'from'         => null,
                'to'           => null
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteManyByIdsAndTenantId( array $id, int $tenantId ): int
    {
        try {
            $query   = $this->newQuery();
            $deleted = $this->applyTenantFilter( $query, $tenantId )->whereIn( 'id', $id )->delete();

            $this->logOperation( 'deleteManyByIdsAndTenantId', [
                'ids_count'     => count( $id ),
                'tenant_id'     => $tenantId,
                'deleted_count' => $deleted
            ] );

            return $deleted;
        } catch ( Throwable $e ) {
            $this->logError( 'deleteManyByIdsAndTenantId', $e, [
                'ids'       => $id,
                'tenant_id' => $tenantId
            ] );
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateManyByTenantId( array $criteria, array $updates, int $tenantId ): int
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $criteria );

            $updated = $query->update( $updates );

            $this->logOperation( 'updateManyByTenantId', [
                'criteria'      => $criteria,
                'updates'       => $updates,
                'tenant_id'     => $tenantId,
                'updated_count' => $updated
            ] );

            return $updated;
        } catch ( Throwable $e ) {
            $this->logError( 'updateManyByTenantId', $e, [
                'criteria'  => $criteria,
                'updates'   => $updates,
                'tenant_id' => $tenantId
            ] );
            return 0;
        }
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
    public function first(): ?Model
    {
        try {
            return $this->newQuery()->first();
        } catch ( Throwable $e ) {
            $this->logError( 'first', $e );
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function last(): ?Model
    {
        try {
            return $this->newQuery()->latest()->first();
        } catch ( Throwable $e ) {
            $this->logError( 'last', $e );
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
     * Aplica critérios de busca na query
     *
     * @param Builder $query Query builder
     * @param array $criteria Critérios de busca
     * @return Builder Query builder com critérios aplicados
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
     * Aplica ordenação na query
     *
     * @param Builder $query Query builder
     * @param array|null $orderBy Ordenação [campo => direção]
     * @return Builder Query builder com ordenação aplicada
     */
    protected function applyOrderBy( Builder $query, ?array $orderBy ): Builder
    {
        if ( $orderBy ) {
            foreach ( $orderBy as $field => $direction ) {
                $query->orderBy( $field, $direction );
            }
        }

        return $query;
    }

    /**
     * Valida se uma entidade pertence ao tenant especificado
     *
     * @param Model $entity Entidade a ser validada
     * @param int $tenantId ID do tenant
     * @return bool True se a entidade pertence ao tenant, false caso contrário
     */
    protected function validateTenantOwnership( Model $entity, int $tenantId ): bool
    {
        if ( $entity instanceof Model ) {
            $tid = $entity->getAttribute( 'tenant_id' );
            if ( $tid !== null ) {
                return $tid === $tenantId;
            }
        }
        return true;
    }

    /**
     * Registra uma operação no log
     *
     * @param string $operation Nome da operação
     * @param array $context Contexto da operação
     * @return void
     */
    protected function logOperation( string $operation, array $context = [] ): void
    {
        Log::info( "Repository operation: {$operation}", array_merge( [
            'repository' => static::class,
            'model'      => $this->modelClass
        ], $context ) );
    }

    /**
     * Registra um erro no log
     *
     * @param string $operation Nome da operação
     * @param Throwable $exception Exceção ocorrida
     * @param array $context Contexto da operação
     * @return void
     */
    protected function logError( string $operation, Throwable $exception, array $context = [] ): void
    {
        Log::error( "Repository error in {$operation}: " . $exception->getMessage(), array_merge( [
            'repository' => static::class,
            'model'      => $this->modelClass,
            'exception'  => $exception
        ], $context ) );
    }

}
