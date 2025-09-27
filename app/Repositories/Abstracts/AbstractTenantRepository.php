<?php

declare(strict_types=1);

namespace App\Repositories\Abstracts;

use App\Interfaces\TenantRepositoryInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Repositório abstrato base para operações tenant-aware
 *
 * Esta classe implementa todos os métodos básicos para repositórios que trabalham
 * com dados isolados por tenant, fornecendo funcionalidade completa de CRUD
 * com isolamento automático de dados por tenant_id
 */
abstract class AbstractTenantRepository implements TenantRepositoryInterface
{
    protected Model   $model;
    protected Builder $query;
    protected ?int    $tenantId            = null;
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
     * Define o ID do tenant para o contexto do repositório
     *
     * @param int $tenantId ID do tenant
     * @return self
     */
    public function setTenantId( int $tenantId ): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Obtém o ID do tenant atual do contexto
     *
     * @return int|null ID do tenant ou null se não definido
     */
    public function getTenantId(): ?int
    {
        return $this->tenantId ?? Auth::user()?->tenant_id ?? session( 'tenant_id' );
    }

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
     * Cria um novo registro com tenant_id
     *
     * @param array $data Dados do registro
     * @return Model Registro criado
     */
    public function create( array $data ): Model
    {
        $this->validateTenantData( $data );
        return $this->model->create( $data );
    }

    /**
     * Atualiza um registro existente
     *
     * @param Model $model Registro a ser atualizado
     * @param array $data Dados para atualização
     * @return Model Registro atualizado
     */
    public function update( Model $model, array $data ): Model
    {
        $this->validateTenantAccess( $model );
        $this->validateTenantData( $data );

        $model->update( $data );
        return $model->fresh();
    }

    /**
     * Remove um registro
     *
     * @param Model $model Registro a ser removido
     * @return bool True se removido com sucesso
     */
    public function delete( Model $model ): bool
    {
        $this->validateTenantAccess( $model );
        return $model->delete();
    }

    /**
     * Busca um registro por ID e tenant_id
     *
     * @param int $id ID do registro
     * @param int $tenantId ID do tenant
     * @return Model|null Registro encontrado ou null
     */
    public function findByIdAndTenant( int $id, int $tenantId ): ?Model
    {
        try {
            $query  = $this->newQuery();
            $entity = $this->applyTenantFilter( $query, $tenantId )->find( $id );

            $this->logOperation( 'findByIdAndTenant', [
                'id'        => $id,
                'tenant_id' => $tenantId,
                'found'     => $entity !== null
            ] );

            return $entity;
        } catch ( Throwable $e ) {
            $this->logError( 'findByIdAndTenant', $e, [ 'id' => $id, 'tenant_id' => $tenantId ] );
            return null;
        }
    }

    /**
     * Busca registros por critérios e tenant_id
     *
     * @param array $criteria Critérios de busca
     * @param int $tenantId ID do tenant
     * @return Collection Coleção de registros encontrados
     */
    public function findByCriteriaAndTenant( array $criteria, int $tenantId ): Collection
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $criteria );

            $entities = $query->get();

            $this->logOperation( 'findByCriteriaAndTenant', [
                'criteria'    => $criteria,
                'tenant_id'   => $tenantId,
                'found_count' => $entities->count()
            ] );

            return $entities;
        } catch ( Throwable $e ) {
            $this->logError( 'findByCriteriaAndTenant', $e, [
                'criteria'  => $criteria,
                'tenant_id' => $tenantId
            ] );
            return collect();
        }
    }

    /**
     * Busca um registro por critérios e tenant_id
     *
     * @param array $criteria Critérios de busca
     * @param int $tenantId ID do tenant
     * @return Model|null Registro encontrado ou null
     */
    public function findOneByCriteriaAndTenant( array $criteria, int $tenantId ): ?Model
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $criteria );

            $entity = $query->first();

            $this->logOperation( 'findOneByCriteriaAndTenant', [
                'criteria'  => $criteria,
                'tenant_id' => $tenantId,
                'found'     => $entity !== null
            ] );

            return $entity;
        } catch ( Throwable $e ) {
            $this->logError( 'findOneByCriteriaAndTenant', $e, [
                'criteria'  => $criteria,
                'tenant_id' => $tenantId
            ] );
            return null;
        }
    }

    /**
     * Busca registro por slug e tenant_id
     *
     * @param string $slug Slug do registro
     * @param int $tenantId ID do tenant
     * @return Model|null Registro encontrado ou null
     */
    public function findBySlugAndTenant( string $slug, int $tenantId ): ?Model
    {
        return $this->findOneByCriteriaAndTenant( [ 'slug' => $slug ], $tenantId );
    }

    /**
     * Busca registro por código e tenant_id
     *
     * @param string $code Código do registro
     * @param int $tenantId ID do tenant
     * @return Model|null Registro encontrado ou null
     */
    public function findByCodeAndTenant( string $code, int $tenantId ): ?Model
    {
        return $this->findOneByCriteriaAndTenant( [ 'code' => $code ], $tenantId );
    }

    /**
     * Atualiza múltiplos registros por critérios e tenant_id
     *
     * @param array $criteria Critérios para seleção
     * @param array $updates Dados para atualização
     * @param int $tenantId ID do tenant
     * @return int Número de registros atualizados
     */
    public function updateManyByTenant( array $criteria, array $updates, int $tenantId ): int
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $criteria );

            $updated = $query->update( $updates );

            $this->logOperation( 'updateManyByTenant', [
                'criteria'      => $criteria,
                'updates'       => $updates,
                'tenant_id'     => $tenantId,
                'updated_count' => $updated
            ] );

            return $updated;
        } catch ( Throwable $e ) {
            $this->logError( 'updateManyByTenant', $e, [
                'criteria'  => $criteria,
                'updates'   => $updates,
                'tenant_id' => $tenantId
            ] );
            return 0;
        }
    }

    /**
     * Remove múltiplos registros por critérios e tenant_id
     *
     * @param array $criteria Critérios para seleção
     * @param int $tenantId ID do tenant
     * @return int Número de registros removidos
     */
    public function deleteManyByTenant( array $criteria, int $tenantId ): int
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $criteria );

            $deleted = $query->delete();

            $this->logOperation( 'deleteManyByTenant', [
                'criteria'      => $criteria,
                'tenant_id'     => $tenantId,
                'deleted_count' => $deleted
            ] );

            return $deleted;
        } catch ( Throwable $e ) {
            $this->logError( 'deleteManyByTenant', $e, [
                'criteria'  => $criteria,
                'tenant_id' => $tenantId
            ] );
            return 0;
        }
    }

    /**
     * Busca registros paginados por tenant_id
     *
     * @param int $tenantId ID do tenant
     * @param int $perPage Itens por página
     * @param array $criteria Critérios de busca
     * @return LengthAwarePaginator Paginator com resultados
     */
    public function paginateByTenant( int $tenantId, int $perPage = 15, array $criteria = [] ): LengthAwarePaginator
    {
        try {
            $query = $this->newQuery();
            $query = $this->applyTenantFilter( $query, $tenantId );
            $query = $this->applyCriteria( $query, $criteria );

            return $query->paginate( $perPage );
        } catch ( Throwable $e ) {
            $this->logError( 'paginateByTenant', $e, [
                'tenant_id' => $tenantId,
                'per_page'  => $perPage
            ] );

            return new LengthAwarePaginator( [], 0, $perPage );
        }
    }

    /**
     * Valida se um registro pertence ao tenant especificado
     *
     * @param Model $model Registro a ser validado
     * @param int $tenantId ID do tenant
     * @return bool True se pertence ao tenant, false caso contrário
     */
    public function validateTenantOwnership( Model $model, int $tenantId ): bool
    {
        if ( !$this->modelHasTenantScope() ) {
            return true;
        }

        $modelTenantId = $model->getAttribute( 'tenant_id' );
        return $modelTenantId === $tenantId;
    }

    /**
     * Valida se um valor é único em um campo para um tenant
     *
     * @param string $field Campo a ser verificado
     * @param mixed $value Valor a ser verificado
     * @param int $tenantId ID do tenant
     * @param int|null $excludeId ID a ser excluído da verificação (para updates)
     * @return bool True se é único, false caso contrário
     */
    public function validateUniqueInTenant( string $field, mixed $value, int $tenantId, ?int $excludeId = null ): bool
    {
        $query = $this->newQuery()
            ->where( $field, $value )
            ->where( 'tenant_id', $tenantId );

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
     * @return int Número de registros
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
     * Valida acesso do tenant ao modelo
     *
     * @param Model $model Modelo a ser validado
     * @throws InvalidArgumentException Se não tem acesso
     */
    protected function validateTenantAccess( Model $model ): void
    {
        if ( !$this->modelHasTenantScope() ) {
            return;
        }

        $currentTenantId = $this->getTenantId();
        $modelTenantId   = $model->getAttribute( 'tenant_id' );

        if ( $currentTenantId && $currentTenantId !== $modelTenantId ) {
            throw new InvalidArgumentException( 'Acesso negado: recurso não pertence ao tenant atual' );
        }
    }

    /**
     * Valida dados do tenant
     *
     * @param array $data Dados a serem validados
     * @throws InvalidArgumentException Se tenant_id não corresponde
     */
    protected function validateTenantData( array $data ): void
    {
        if ( !$this->modelHasTenantScope() ) {
            return;
        }

        $currentTenantId = $this->getTenantId();

        if ( $currentTenantId && isset( $data[ 'tenant_id' ] ) && $data[ 'tenant_id' ] !== $currentTenantId ) {
            throw new InvalidArgumentException( 'Tenant ID nos dados não corresponde ao tenant atual' );
        }
    }

    /**
     * Verifica se o modelo tem escopo de tenant
     *
     * @return bool
     */
    protected function modelHasTenantScope(): bool
    {
        return in_array( 'App\Models\Traits\TenantScoped', class_uses_recursive( $this->model ) );
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
        Log::info( "Tenant Repository operation: {$operation}", array_merge( [
            'repository' => static::class,
            'model'      => $this->getModelClass(),
            'tenant_id'  => $this->getTenantId()
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
        Log::error( "Tenant Repository error in {$operation}: " . $exception->getMessage(), array_merge( [
            'repository' => static::class,
            'model'      => $this->getModelClass(),
            'tenant_id'  => $this->getTenantId(),
            'exception'  => $exception
        ], $context ) );
    }

}
