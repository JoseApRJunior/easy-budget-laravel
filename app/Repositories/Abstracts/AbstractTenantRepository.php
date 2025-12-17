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
abstract class AbstractTenantRepository implements BaseRepositoryInterface, TenantRepositoryInterface
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
        try {
            return $this->model->findOrFail( $id );
        } catch ( ModelNotFoundException $e ) {
            return null;
        }
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
        $model = $this->find( $id );

        if ( !$model ) {
            return false;
        }

        return $model->delete();
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

        // Aplica filtros de tenant automaticamente via Global Scope
        $this->applyFilters( $query, $criteria );

        // Aplica ordenação usando trait
        $this->applyOrderBy( $query, $orderBy );

        // Aplica limite e offset
        if ( $offset !== null ) {
            $query->offset( $offset );
        }
        if ( $limit !== null ) {
            $query->limit( $limit );
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function paginateByTenant(
        int $perPage = 15,
        array $filters = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Aplica filtros de tenant automaticamente via Global Scope
        $this->applyFilters( $query, $filters );

        // Aplica ordenação usando trait
        $this->applyOrderBy( $query, $orderBy );

        return $query->paginate( $perPage );
    }

    /**
     * {@inheritdoc}
     */
    public function countByTenant( array $filters = [] ): int
    {
        $query = $this->model->newQuery();

        // Aplica filtros de tenant automaticamente via Global Scope
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
        $query = $this->model->where( $field, $value );

        if ( $excludeId !== null ) {
            $query->where( 'id', '!=', $excludeId );
        }

        return $query->exists();
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
        $query = $this->model->whereIn( 'id', $ids );

        if ( !empty( $with ) ) {
            $query->with( $with );
        }

        return $query->get();
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
     * Este método pode ser sobrescrito por repositories específicos quando necessário,
     * fornecendo uma base consistente para todos os repositories tenant-scoped.
     *
     * Funcionalidades incluídas:
     * - Eager loading paramétrico via $with
     * - Suporte a soft delete automático
     * - Per page dinâmico via filtro
     * - Ordenação customizável
     * - Filtros avançados via RepositoryFiltersTrait
     *
     * @param array<string, mixed> $filters Filtros a aplicar (ex: ['search' => 'termo', 'active' => true, 'per_page' => 20])
     * @param int $perPage Número padrão de itens por página (15)
     * @param array<string> $with Relacionamentos para eager loading (ex: ['category', 'inventory'])
     * @param array<string, string>|null $orderBy Ordenação personalizada (ex: ['name' => 'asc', 'created_at' => 'desc'])
     * @return LengthAwarePaginator Resultado paginado
     *
     * @example Uso básico:
     * ```php
     * $results = $repository->getPaginated();
     * ```
     *
     * @example Com filtros:
     * ```php
     * $results = $repository->getPaginated([
     *     'search' => 'produto',
     *     'active' => true,
     *     'per_page' => 20
     * ]);
     * ```
     *
     * @example Com eager loading:
     * ```php
     * $results = $repository->getPaginated([], 15, ['category', 'inventory']);
     * ```
     *
     * @example Com ordenação customizada:
     * ```php
     * $results = $repository->getPaginated([], 15, [], ['created_at' => 'desc', 'name' => 'asc']);
     * ```
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Eager loading paramétrico
        if ( !empty( $with ) ) {
            $query->with( $with );
        }

        // Aplicar filtros avançados
        $this->applyFilters( $query, $filters );

        // Aplicar filtro de soft delete se necessário
        $this->applySoftDeleteFilter( $query, $filters );

        // Aplicar ordenação
        $this->applyOrderBy( $query, $orderBy );

        // Per page dinâmico
        $effectivePerPage = $this->getEffectivePerPage( $filters, $perPage );

        return $query->paginate( $effectivePerPage );
    }

}
