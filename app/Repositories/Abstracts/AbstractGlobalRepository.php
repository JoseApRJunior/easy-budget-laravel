<?php

declare(strict_types=1);

namespace App\Repositories\Abstracts;

use App\Contracts\Interfaces\GlobalRepositoryInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Repositório abstrato base para operações globais
 *
 * Esta classe implementa todos os métodos básicos para repositórios que trabalham
 * com dados globais, sem isolamento por tenant_id
 *
 * Atualizado para usar a nova estrutura unificada de contratos/interfaces
 */
abstract class AbstractGlobalRepository implements GlobalRepositoryInterface
{
    protected Model   $model;
    protected Builder $query;
    protected bool    $resetAfterOperation = true;
    protected string  $modelClass;

    /**
     * Construtor do repositório
     *
     * @throws Exception Se a classe do modelo não for definida
     */
    public function __construct()
    {
        $this->model = $this->makeModel();
        $this->reset();
    }

    /**
     * Cria uma nova instância do modelo
     *
     * @return Model
     * @throws Exception
     */
    abstract protected function makeModel(): Model;

    /**
     * Retorna a classe do modelo associado ao repositório
     *
     * @return string Nome da classe do modelo
     */
    public function getModelClass(): string
    {
        return $this->modelClass ?? '';
    }

    /**
     * Retorna o nome da tabela do modelo
     *
     * @return string Nome da tabela
     */
    public function getTable(): string
    {
        return $this->model->getTable();
    }

    /**
     * Cria uma nova instância do modelo
     *
     * @param array $attributes Atributos iniciais do modelo
     * @return Model Nova instância do modelo
     */
    public function newModel( array $attributes = [] ): Model
    {
        $class = $this->getModelClass();
        return new $class( $attributes );
    }

    /**
     * Retorna uma nova instância de query builder para o modelo
     *
     * @return Builder Query builder instance
     */
    public function newQuery(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Encontra um registro por ID
     *
     * @param int|string $id ID do registro
     * @return Model|null Registro encontrado ou null
     */
    public function find( int|string $id ): ?Model
    {
        $result = $this->query->find( $id );
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Encontra um registro por ID ou falha com exception
     *
     * @param int|string $id ID do registro
     * @return Model Registro encontrado
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail( int|string $id ): Model
    {
        $result = $this->query->findOrFail( $id );
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Encontra múltiplos registros por IDs
     *
     * @param array $ids Array de IDs
     * @return Collection Coleção de registros encontrados
     */
    public function findMany( array $ids ): Collection
    {
        $result = $this->query->whereIn( 'id', $ids )->get();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Cria um novo registro
     *
     * @param array $data Dados do registro
     * @return Model Registro criado
     */
    public function create( array $data ): Model
    {
        return $this->model->create( $data );
    }

    /**
     * Atualiza um registro existente
     *
     * @param int $id ID do registro a ser atualizado
     * @param array $data Dados para atualização
     * @return bool True se atualizado com sucesso
     */
    public function update( int $id, array $data ): bool
    {
        $model = $this->find( $id );
        if ( !$model ) {
            return false;
        }

        $model->update( $data );
        return true;
    }

    /**
     * Remove um registro
     *
     * @param int $id ID do registro a ser removido
     * @return bool True se removido com sucesso
     */
    public function delete( int $id ): bool
    {
        $model = $this->find( $id );
        if ( !$model ) {
            return false;
        }

        return $model->delete();
    }

    /**
     * Busca registros por critérios
     *
     * @param array $criteria Critérios de busca
     * @return Collection Coleção de registros encontrados
     */
    public function findByCriteria( array $criteria ): Collection
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyCriteria( $query, $criteria );

            $entities = $query->get();

            $this->logOperation( 'findByCriteria', [
                'criteria'    => $criteria,
                'found_count' => $entities->count()
            ] );

            return $entities;
        } catch ( Throwable $e ) {
            $this->logError( 'findByCriteria', $e, [ 'criteria' => $criteria ] );
            return collect();
        }
    }

    /**
     * Busca um registro por critérios
     *
     * @param array $criteria Critérios de busca
     * @return Model|null Registro encontrado ou null
     */
    public function findOneByCriteria( array $criteria ): ?Model
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyCriteria( $query, $criteria );

            $entity = $query->first();

            $this->logOperation( 'findOneByCriteria', [
                'criteria' => $criteria,
                'found'    => $entity !== null
            ] );

            return $entity;
        } catch ( Throwable $e ) {
            $this->logError( 'findOneByCriteria', $e, [ 'criteria' => $criteria ] );
            return null;
        }
    }

    /**
     * Busca registro por slug
     *
     * @param string $slug Slug do registro
     * @return Model|null Registro encontrado ou null
     */
    public function findBySlug( string $slug ): ?Model
    {
        return $this->findOneByCriteria( [ 'slug' => $slug ] );
    }

    /**
     * Atualiza múltiplos registros por critérios
     *
     * @param array $criteria Critérios para seleção
     * @param array $updates Dados para atualização
     * @return int Número de registros atualizados
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
     * Remove múltiplos registros por critérios
     *
     * @param array $criteria Critérios para seleção
     * @return int Número de registros removidos
     */
    public function deleteMany( array $criteria ): int
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyCriteria( $query, $criteria );

            $deleted = $query->delete();

            $this->logOperation( 'deleteMany', [
                'criteria'      => $criteria,
                'deleted_count' => $deleted
            ] );

            return $deleted;
        } catch ( Throwable $e ) {
            $this->logError( 'deleteMany', $e, [ 'criteria' => $criteria ] );
            return 0;
        }
    }

    /**
     * Busca registros paginados
     *
     * @param int $perPage Itens por página
     * @param array $criteria Critérios de busca
     * @return LengthAwarePaginator Paginator com resultados
     */
    public function paginate( int $perPage = 15, array $criteria = [] ): LengthAwarePaginator
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyCriteria( $query, $criteria );

            return $query->paginate( $perPage );
        } catch ( Throwable $e ) {
            $this->logError( 'paginate', $e, [ 'per_page' => $perPage ] );

            return new LengthAwarePaginator( [], 0, $perPage );
        }
    }

    /**
     * Valida se um valor é único em um campo
     *
     * @param string $field Campo a ser verificado
     * @param mixed $value Valor a ser verificado
     * @param int|null $excludeId ID a ser excluído da verificação (para updates)
     * @return bool True se é único, false caso contrário
     */
    public function validateUnique( string $field, mixed $value, ?int $excludeId = null ): bool
    {
        $query = $this->newQuery()->where( $field, $value );

        if ( $excludeId ) {
            $query->where( 'id', '!=', $excludeId );
        }

        return !$query->exists();
    }

    /**
     * Executa uma transação de banco de dados
     *
     * @param callable $callback Função a ser executada dentro da transação
     * @return mixed Retorno da função callback
     * @throws \Throwable Se ocorrer erro na transação
     */
    public function transaction( callable $callback ): mixed
    {
        return DB::transaction( $callback );
    }

    /**
     * Inicia uma transação manual
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    /**
     * Confirma uma transação manual
     *
     * @return void
     */
    public function commit(): void
    {
        DB::commit();
    }

    /**
     * Desfaz uma transação manual
     *
     * @return void
     */
    public function rollback(): void
    {
        DB::rollback();
    }

    /**
     * Verifica se existem registros na tabela
     *
     * @return bool True se existem registros, false caso contrário
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
     * Conta o número total de registros
     *
     * @param array $filters Filtros opcionais para contar
     * @return int Número de registros
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
     * Trunca a tabela (remove todos os registros)
     *
     * @return bool True se bem-sucedido, false caso contrário
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
     * Realiza refresh dos dados da entidade a partir do banco
     *
     * @param Model $entity Entidade a ser atualizada
     * @return Model|null Entidade atualizada ou null se não encontrada
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
     * Encontra o primeiro registro
     *
     * @return Model|null Primeiro registro ou null
     */
    public function first(): ?Model
    {
        try {
            $result = $this->newQuery()->first();
            $this->resetIfNeeded();
            return $result;
        } catch ( Throwable $e ) {
            $this->logError( 'first', $e );
            return null;
        }
    }

    /**
     * Encontra o último registro
     *
     * @return Model|null Último registro ou null
     */
    public function last(): ?Model
    {
        try {
            $result = $this->newQuery()->orderBy( $this->model->getKeyName(), 'desc' )->first();
            $this->resetIfNeeded();
            return $result;
        } catch ( Throwable $e ) {
            $this->logError( 'last', $e );
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
     *
     * @return self
     */
    protected function reset(): self
    {
        $this->query = $this->model->newQuery();
        return $this;
    }

    /**
     * Registra uma operação no log
     *
     * @param string $operation Nome da operação
     * @param array $context Contexto da operação
     */
    protected function logOperation( string $operation, array $context = [] ): void
    {
        Log::info( "Global Repository operation: {$operation}", array_merge( [
            'repository' => static::class,
            'model'      => $this->getModelClass()
        ], $context ) );
    }

    /**
     * Registra um erro no log
     *
     * @param string $operation Nome da operação
     * @param Throwable $exception Exceção ocorrida
     * @param array $context Contexto da operação
     */
    protected function logError( string $operation, Throwable $exception, array $context = [] ): void
    {
        Log::error( "Global Repository error in {$operation}: " . $exception->getMessage(), array_merge( [
            'repository' => static::class,
            'model'      => $this->getModelClass(),
            'exception'  => $exception
        ], $context ) );
    }

    // ========== MÉTODOS ESPECÍFICOS DA INTERFACE GlobalRepositoryInterface ==========

    /**
     * Busca todos os registros sem restrição de tenant
     */
    public function findAllGlobal(): Collection
    {
        return $this->all();
    }

    /**
     * Busca registro por ID sem restrição de tenant
     */
    public function findGlobal( int $id ): ?Model
    {
        return $this->find( $id );
    }

    /**
     * Busca registros com filtros sem restrição de tenant
     */
    public function findByGlobal( array $criteria ): Collection
    {
        return $this->findByCriteria( $criteria );
    }

    /**
     * Conta registros sem restrição de tenant
     */
    public function countGlobal( array $filters = [] ): int
    {
        return $this->count( $filters );
    }

    /**
     * Cria registro global (sem tenant)
     */
    public function createGlobal( array $data ): Model
    {
        return $this->create( $data );
    }

    /**
     * Atualiza registro global
     */
    public function updateGlobal( int $id, array $data ): bool
    {
        return $this->update( $id, $data );
    }

    /**
     * Remove registro global
     */
    public function deleteGlobal( int $id ): bool
    {
        return $this->delete( $id );
    }

    /**
     * Busca registros paginados sem restrição de tenant
     */
    public function paginateGlobal( int $perPage = 15, array $filters = [] ): LengthAwarePaginator
    {
        return $this->paginate( $perPage, $filters );
    }

}
