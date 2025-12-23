<?php
declare(strict_types=1);

namespace App\Repositories\Abstracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório abstrato base para modelos tenant-scoped.
 *
 * Esta classe é a espinha dorsal para todos os repositórios que operam em
 * contexto multi-tenant, garantindo isolamento automático de dados por empresa.
 * Assume que o Model possui Global Scope que filtra automaticamente por tenant_id.
 *
 * Implementa diretamente BaseRepositoryInterface e TenantRepositoryInterface
 * sem herança desnecessária, promovendo menor acoplamento e maior flexibilidade.
 *
 * Funcionalidades principais:
 * - Isolamento automático por tenant
 * - Operações CRUD com contexto tenant
 * - Filtros e paginação avançados
 * - Busca por slug e código único
 * - Validação de unicidade dentro do tenant
 * - Operações em lote
 *
 * @package App\Repositories\Abstracts
 *
 * @example Exemplo de implementação concreta:
 * ```php
 * class ProductRepository extends AbstractTenantRepository
 * {
 *     protected function makeModel(): Model
 *     {
 *         return new Product(); // Model com TenantScoped trait
 *     }
 *
 *     public function findActiveByCategory(int $categoryId): Collection
 *     {
 *         return $this->model->where('category_id', $categoryId)
 *                           ->where('active', true)
 *                           ->get();
 *     }
 * }
 * ```
 *
 * @example Uso em Service Layer:
 * ```php
 * class ProductService extends AbstractBaseService
 * {
 *     public function __construct(ProductRepository $repository)
 *     {
 *         parent::__construct($repository);
 *     }
 *
 *     public function getActiveProducts(): ServiceResult
 *     {
 *         $products = $this->repository->getAllByTenant(
 *             ['active' => true],
 *             ['name' => 'asc']
 *         );
 *         return $this->success($products);
 *     }
 * }
 * ```
 *
 * @example Cenários de uso recomendados:
 * - **Produtos/Serviços** - Cada empresa gerencia seu catálogo
 * - **Clientes/CRM** - Dados isolados por empresa
 * - **Orçamentos/Faturas** - Controle financeiro por tenant
 * - **Configurações específicas** - Personalização por empresa
 */
abstract class AbstractTenantRepository implements TenantRepositoryInterface
{
    use RepositoryFiltersTrait;

    protected Model $model;

    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    /**
     * Define o Model a ser utilizado. Deve ser um Model com HasTenantScope.
     */
    abstract protected function makeModel(): Model;

    // --------------------------------------------------------------------------
    // IMPLEMENTAÇÃO DOS MÉTODOS BÁSICOS DO BaseRepositoryInterface
    // --------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function find( int $id ): ?Model
    {
        return $this->model->find( $id );
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * {@inheritdoc}
     */
    public function create( array $data ): Model
    {
        return $this->model->create( $data );
    }

    /**
     * {@inheritdoc}
     */
    public function update( int $id, array $data ): ?Model
    {
        $model = $this->find( $id );

        if ( !$model ) {
            return null;
        }

        $model->update( $data );
        return $model->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete( int $id ): bool
    {
        return (bool) $this->model->where( 'id', $id )->delete();
    }

    // --------------------------------------------------------------------------
    // IMPLEMENTAÇÃO DOS MÉTODOS ESPECÍFICOS DO TenantRepositoryInterface
    // --------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function getAllByTenant(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        $query = $this->model->newQuery();

        // Aplica filtros e ordenação usando trait
        return $this->applyFilters( $query, $criteria )
            ->when( $orderBy, fn( $q ) => $this->applyOrderBy( $q, $orderBy ) )
            ->when( $offset !== null, fn( $q ) => $q->offset( $offset ) )
            ->when( $limit !== null, fn( $q ) => $q->limit( $limit ) )
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function countByTenant( array $filters = [] ): int
    {
        $query = $this->model->newQuery();
        $this->applyFilters( $query, $filters );

        return $query->count();
    }

    /**
     * Conta incluindo deletadas.
     */
    public function countByTenantWithTrashed( array $filters = [] ): int
    {
        $query = $this->model->newQuery()->withTrashed();
        $this->applyFilters( $query, $filters );

        return $query->count();
    }

    /**
     * Conta apenas deletadas.
     */
    public function countOnlyTrashedByTenant( array $filters = [] ): int
    {
        $query = $this->model->newQuery()->onlyTrashed();
        $this->applyFilters( $query, $filters );

        return $query->count();
    }

    /**
     * {@inheritdoc}
     */
    public function findByTenantAndSlug( string $slug ): ?Model
    {
        return $this->model->where( 'slug', $slug )->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByTenantAndCode( string $code ): ?Model
    {
        return $this->model->where( 'code', $code )->first();
    }

    /**
     * {@inheritdoc}
     */
    public function isUniqueInTenant( string $field, mixed $value, ?int $excludeId = null ): bool
    {
        return $this->model->where( $field, $value )
            ->when( $excludeId !== null, fn( $q ) => $q->where( 'id', '!=', $excludeId ) )
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function findManyByTenant( array $ids ): Collection
    {
        return $this->model->whereIn( 'id', $ids )->get();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteManyByTenant( array $ids ): int
    {
        return $this->model->whereIn( 'id', $ids )->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function searchByTenant(
        string $search,
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): Collection {
        $query = $this->model->newQuery();

        if ( !empty( $search ) ) {
            $filters[ 'search' ] = $search;
            $this->applySearchFilter( $query, $filters, [ 'name', 'description' ] );
        }

        return $this->applyFilters( $query, $filters )
            ->when( $orderBy, fn( $q ) => $this->applyOrderBy( $q, $orderBy ) )
            ->when( $limit !== null, fn( $q ) => $q->limit( $limit ) )
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveByTenant(
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): Collection {
        $query = $this->model->newQuery();

        // Se usar SoftDeletes, o default do newQuery() já filtra ativos.
        // Caso contrário, filtramos manualmente se a coluna existir.
        if ( !method_exists( $this->model, 'runSoftDelete' ) && $this->isValidField( 'deleted_at' ) ) {
            $query->whereNull( 'deleted_at' );
        }

        $this->applyFilters( $query, $filters );
        $this->applyOrderBy( $query, $orderBy );

        return $query->when( $limit !== null, fn( $q ) => $q->limit( $limit ) )
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getDeletedByTenant(
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): Collection {
        $query = $this->model->newQuery();

        if ( method_exists( $this->model, 'runSoftDelete' ) ) {
            $query->onlyTrashed();
        } else {
            $query->whereNotNull( 'deleted_at' );
        }

        $this->applyFilters( $query, $filters );
        $this->applyOrderBy( $query, $orderBy );

        return $query->when( $limit !== null, fn( $q ) => $q->limit( $limit ) )
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function restoreManyByTenant( array $ids ): int
    {
        return $this->model->whereIn( 'id', $ids )
            ->whereNotNull( 'deleted_at' )
            ->update( [ 'deleted_at' => null ] );
    }

    // --------------------------------------------------------------------------
    // MÉTODOS AUXILIARES PROTEGIDOS
    // --------------------------------------------------------------------------

    /**
     * Busca registros com relacionamento específico carregado.
     *
     * @param array<int> $ids
     * @param array<string> $with
     * @return Collection<Model>
     */
    public function findManyWithRelations( array $ids, array $with = [] ): Collection
    {
        return $this->model->whereIn( 'id', $ids )
            ->when( !empty( $with ), fn( $q ) => $q->with( $with ) )
            ->get();
    }

    /**
     * Busca registros por múltiplos critérios dentro do tenant.
     *
     * @param array<string, mixed> $criteria
     * @return Collection<Model>
     */
    public function findByMultipleCriteria( array $criteria ): Collection
    {
        $query = $this->model->newQuery();
        $this->applyFilters( $query, $criteria );

        return $query->get();
    }

    // --------------------------------------------------------------------------
    // MÉTODO PADRÃO DE PAGINAÇÃO (PADRONIZAÇÃO)
    // --------------------------------------------------------------------------

    /**
     * Método padrão de paginação com funcionalidades avançadas.
     *
     * @param array<string, mixed> $filters Filtros a aplicar
     * @param int $perPage Número padrão de itens por página
     * @param array<string> $with Relacionamentos para eager loading
     * @param array<string, string>|null $orderBy Ordenação personalizada
     * @return LengthAwarePaginator Resultado paginado
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 10,
        array $with = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        return $this->model->newQuery()
            ->when( !empty( $with ), fn( $q ) => $q->with( $with ) )
            ->tap( fn( $q ) => $this->applyFilters( $q, $filters ) )
            ->tap( fn( $q ) => $this->applySoftDeleteFilter( $q, $filters ) )
            ->tap( fn( $q ) => $this->applyOrderBy( $q, $orderBy ) )
            ->paginate( $this->getEffectivePerPage( $filters, $perPage ) );
    }

}
