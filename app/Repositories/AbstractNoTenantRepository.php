<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\BaseRepositoryInterface;
use App\Interfaces\RepositoryNoTenantInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Repositório abstrato base para operações não tenant-aware usando Eloquent ORM
 *
 * Esta classe implementa todos os métodos básicos para repositórios que trabalham
 * com dados globais (não isolados por tenant), como roles, permissions, plans, etc.
 * Fornece funcionalidade completa de CRUD sem isolamento de tenant_id
 */
abstract class AbstractNoTenantRepository implements RepositoryNoTenantInterface
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
     * {@inheritdoc}
     */
    public function findById( int $id ): ?Model
    {
        try {
            $entity = $this->newQuery()->find( $id );

            $this->logOperation( 'findById', [ 
                'id'    => $id,
                'found' => $entity !== null
            ] );

            return $entity;
        } catch ( Throwable $e ) {
            $this->logError( 'findById', $e, [ 'id' => $id ] );
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findManyByIds( array $id ): array
    {
        try {
            $entities = $this->newQuery()->whereIn( 'id', $id )->get();

            $this->logOperation( 'findManyByIds', [ 
                'ids_count'   => count( $id ),
                'found_count' => $entities->count()
            ] );

            return $entities->all();
        } catch ( Throwable $e ) {
            $this->logError( 'findManyByIds', $e, [ 'ids' => $id ] );
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        try {
            $query = $this->newQuery();
            $query = $this->applyCriteria( $query, $criteria );
            $query = $this->applyOrderBy( $query, $orderBy );

            if ( $limit !== null ) {
                $query->limit( $limit );
            }

            if ( $offset !== null ) {
                $query->offset( $offset );
            }

            $entities = $query->get();

            $this->logOperation( 'findBy', [ 
                'criteria'    => $criteria,
                'found_count' => $entities->count()
            ] );

            return $entities->all();
        } catch ( Throwable $e ) {
            $this->logError( 'findBy', $e, [ 'criteria' => $criteria ] );
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy( array $criteria ): ?Model
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyCriteria( $query, $criteria );

            $entity = $query->first();

            $this->logOperation( 'findOneBy', [ 
                'criteria' => $criteria,
                'found'    => $entity !== null
            ] );

            return $entity;
        } catch ( Throwable $e ) {
            $this->logError( 'findOneBy', $e, [ 'criteria' => $criteria ] );
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findAll( array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyCriteria( $query, $criteria );
            $query = $this->applyOrderBy( $query, $orderBy );

            if ( $limit !== null ) {
                $query->limit( $limit );
            }

            if ( $offset !== null ) {
                $query->offset( $offset );
            }

            $entities = $query->get();

            $this->logOperation( 'findAll', [ 
                'criteria'    => $criteria,
                'found_count' => $entities->count()
            ] );

            return $entities->all();
        } catch ( Throwable $e ) {
            $this->logError( 'findAll', $e, [ 'criteria' => $criteria ] );
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug( string $slug ): ?Model
    {
        return $this->findOneBy( [ 'slug' => $slug ] );
    }

    /**
     * {@inheritdoc}
     */
    public function save( Model $entity ): Model|false
    {
        try {
            if ( !( $entity instanceof Model ) ) {
                throw new Exception( 'Entity must be an instance of Illuminate\Database\Eloquent\Model' );
            }

            $saved = $entity->save();

            if ( !$saved ) {
                $this->logError( 'save', new Exception( 'Save operation failed' ), [ 
                    'entity_id' => $entity->getKey()
                ] );
                return false;
            }

            $this->logOperation( 'save', [ 
                'entity_id' => $entity->getKey(),
                'success'   => true
            ] );

            return $entity;
        } catch ( Throwable $e ) {
            $this->logError( 'save', $e, [ 'entity_id' => $entity->getKey() ] );
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update( Model $entity ): Model|false
    {
        try {
            if ( !( $entity instanceof Model ) ) {
                throw new Exception( 'Entity must be an instance of Illuminate\Database\Eloquent\Model' );
            }

            $updated = $entity->save();

            if ( !$updated ) {
                $this->logError( 'update', new Exception( 'Update operation failed' ), [ 
                    'entity_id' => $entity->getKey()
                ] );
                return false;
            }

            $this->logOperation( 'update', [ 
                'entity_id' => $entity->getKey(),
                'success'   => true
            ] );

            return $entity;
        } catch ( Throwable $e ) {
            $this->logError( 'update', $e, [ 'entity_id' => $entity->getKey() ] );
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById( int $id ): bool
    {
        try {
            $deleted = $this->newQuery()->where( 'id', $id )->delete();

            $success = $deleted > 0;

            $this->logOperation( 'deleteById', [ 
                'id'      => $id,
                'success' => $success
            ] );

            return $success;
        } catch ( Throwable $e ) {
            $this->logError( 'deleteById', $e, [ 'id' => $id ] );
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete( Model $entity ): bool
    {
        try {
            if ( !( $entity instanceof Model ) ) {
                throw new Exception( 'Entity must be an instance of Illuminate\Database\Eloquent\Model' );
            }

            $deleted = $entity->delete();

            $this->logOperation( 'delete', [ 
                'entity_id' => $entity->getKey(),
                'success'   => $deleted
            ] );

            return $deleted;
        } catch ( Throwable $e ) {
            $this->logError( 'delete', $e, [ 'entity_id' => $entity->getKey() ] );
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countBy( array $criteria = [] ): int
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyCriteria( $query, $criteria );

            return $query->count();
        } catch ( Throwable $e ) {
            $this->logError( 'countBy', $e, [ 'criteria' => $criteria ] );
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function existsBy( array $criteria ): bool
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyCriteria( $query, $criteria );

            return $query->exists();
        } catch ( Throwable $e ) {
            $this->logError( 'existsBy', $e, [ 'criteria' => $criteria ] );
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $criteria = [],
        ?array $orderBy = null,
    ): array {
        try {
            $query = $this->newQuery();
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
            $this->logError( 'paginate', $e, [ 
                'page'     => $page,
                'per_page' => $perPage
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
    public function deleteManyByIds( array $id ): int
    {
        try {
            $deleted = $this->newQuery()->whereIn( 'id', $id )->delete();

            $this->logOperation( 'deleteManyByIds', [ 
                'ids_count'     => count( $id ),
                'deleted_count' => $deleted
            ] );

            return $deleted;
        } catch ( Throwable $e ) {
            $this->logError( 'deleteManyByIds', $e, [ 'ids' => $id ] );
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateMany( array $criteria, array $updates ): int
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyCriteria( $query, $criteria );

            $updated = $query->update( $updates );

            $this->logOperation( 'updateMany', [ 
                'criteria'      => $criteria,
                'updates'       => $updates,
                'updated_count' => $updated
            ] );

            return $updated;
        } catch ( Throwable $e ) {
            $this->logError( 'updateMany', $e, [ 
                'criteria' => $criteria,
                'updates'  => $updates
            ] );
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findOrderedBy( string $field, string $direction = 'asc', ?int $limit = null ): array
    {
        try {
            $query = $this->newQuery()->orderBy( $field, $direction );

            if ( $limit !== null ) {
                $query->limit( $limit );
            }

            $entities = $query->get();

            $this->logOperation( 'findOrderedBy', [ 
                'field'       => $field,
                'direction'   => $direction,
                'found_count' => $entities->count()
            ] );

            return $entities->all();
        } catch ( Throwable $e ) {
            $this->logError( 'findOrderedBy', $e, [ 
                'field'     => $field,
                'direction' => $direction
            ] );
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findFirstBy( array $criteria, ?array $orderBy = null ): ?Model
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyCriteria( $query, $criteria );
            $query = $this->applyOrderBy( $query, $orderBy );

            $entity = $query->first();

            $this->logOperation( 'findFirstBy', [ 
                'criteria' => $criteria,
                'found'    => $entity !== null
            ] );

            return $entity;
        } catch ( Throwable $e ) {
            $this->logError( 'findFirstBy', $e, [ 'criteria' => $criteria ] );
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findLastBy( array $criteria, ?array $orderBy = null ): ?Model
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyCriteria( $query, $criteria );
            $query = $this->applyOrderBy( $query, $orderBy );

            $entity = $query->latest()->first();

            $this->logOperation( 'findLastBy', [ 
                'criteria' => $criteria,
                'found'    => $entity !== null
            ] );

            return $entity;
        } catch ( Throwable $e ) {
            $this->logError( 'findLastBy', $e, [ 'criteria' => $criteria ] );
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        try {
            return $this->newQuery()->count();
        } catch ( Throwable $e ) {
            $this->logError( 'count', $e );
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
