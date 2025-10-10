<?php
declare(strict_types=1);

namespace App\Repositories\Abstracts;

use App\Repositories\Contracts\GlobalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório abstrato base para operações globais (sem tenant_id).
 *
 * Esta classe fornece funcionalidades avançadas para repositórios que operam
 * em contexto global, sem restrições de tenant. É ideal para entidades que
 * precisam ser acessadas por todos os tenants ou pelo sistema administrativo.
 *
 * @package App\Repositories\Abstracts
 *
 * @example Exemplo de implementação concreta:
 * ```php
 * class CategoryRepository extends AbstractGlobalRepository
 * {
 *     protected function makeModel(): Model
 *     {
 *         return new Category();
 *     }
 *
 *     public function findBySlug(string $slug): ?Category
 *     {
 *         return $this->model->where('slug', $slug)->first();
 *     }
 * }
 * ```
 *
 * @example Uso típico em um Controller:
 * ```php
 * class CategoryController extends Controller
 * {
 *     private CategoryRepository $categoryRepository;
 *
 *     public function __construct(CategoryRepository $categoryRepository)
 *     {
 *         $this->categoryRepository = $categoryRepository;
 *     }
 *
 *     public function index(Request $request)
 *     {
 *         $filters = $request->only(['status', 'type']);
 *         $categories = $this->categoryRepository->paginateGlobal(20, $filters);
 *
 *         return view('categories.index', compact('categories'));
 *     }
 * }
 * ```
 */
abstract class AbstractGlobalRepository implements GlobalRepositoryInterface
{
    protected Model $model;

    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    abstract protected function makeModel(): Model;

    // --------------------------------------------------------------------------
    // MÉTODOS DE LEITURA (READ) - C/ SUFIXO GLOBAL
    // --------------------------------------------------------------------------

    public function findGlobal( int $id ): ?Model
    {
        return $this->model->find( $id );
    }

    public function getAllGlobal(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        $query = $this->model->newQuery();

        // Aplica filtros usando método auxiliar
        $this->applyFilters( $query, $criteria );

        // Aplica ordenação usando método auxiliar
        $this->applyOrderBy( $query, $orderBy );

        // Aplica paginação manual
        if ( $offset !== null ) {
            $query->offset( $offset );
        }
        if ( $limit !== null ) {
            $query->limit( $limit );
        }

        return $query->get();
    }

    // --------------------------------------------------------------------------
    // MÉTODOS DE ESCRITA (WRITE) - C/ SUFIXO GLOBAL
    // --------------------------------------------------------------------------

    public function createGlobal( array $data ): Model
    {
        return $this->model->create( $data );
    }

    public function updateGlobal( int $id, array $data ): ?Model
    {
        $model = $this->findGlobal( $id );

        if ( !$model ) {
            return null;
        }

        $model->update( $data );
        return $model;
    }

    public function deleteGlobal( int $id ): bool
    {
        // Usa a função estática destroy para deleção por ID
        return (bool) $this->model->destroy( $id );
    }

    // --------------------------------------------------------------------------
    // MÉTODOS DE UTILIDADE (UTILITY)
    // --------------------------------------------------------------------------

    public function paginateGlobal( int $perPage = 15, array $filters = [] ): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Aplica filtros usando método auxiliar
        $this->applyFilters( $query, $filters );

        return $query->paginate( $perPage );
    }

    public function countGlobal( array $filters = [] ): int
    {
        $query = $this->model->newQuery();

        // Aplica filtros usando método auxiliar
        $this->applyFilters( $query, $filters );

        return $query->count();
    }

    // --------------------------------------------------------------------------
    // MÉTODOS AUXILIARES PROTEGIDOS
    // --------------------------------------------------------------------------

    /**
    /**
     * Aplica filtros à query de forma segura e consistente.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<string, mixed> $filters Filtros a aplicar (ex: ['status' => 'active', 'type' => 'premium'])
     *
     * @example Uso típico:
     * ```php
     * $filters = [
     *     'status' => 'active',
     *     'category_id' => [1, 2, 3],
     *     'price' => ['operator' => '>', 'value' => 100]
     * ];
     * $this->applyFilters($query, $filters);
     * ```
     */
    protected function applyFilters( $query, array $filters ): void
    {
        if ( empty( $filters ) ) {
            return;
        }

        foreach ( $filters as $field => $value ) {
            if ( is_array( $value ) ) {
                // Suporte a operadores especiais
                if ( isset( $value[ 'operator' ], $value[ 'value' ] ) ) {
                    $query->where( $field, $value[ 'operator' ], $value[ 'value' ] );
                } else {
                    $query->whereIn( $field, $value );
                }
            } elseif ( $value !== null ) {
                $query->where( $field, $value );
            }
        }
    }

    /**
     * Aplica ordenação à query com validação de direção.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<string, string>|null $orderBy
     */
    protected function applyOrderBy( $query, ?array $orderBy ): void
    {
        if ( empty( $orderBy ) ) {
            return;
        }

        foreach ( $orderBy as $field => $direction ) {
            $direction = strtolower( $direction ) === 'desc' ? 'desc' : 'asc';
            $query->orderBy( $field, $direction );
        }
    }

    /**
     * Valida se um campo existe no modelo antes de aplicar filtro.
     *
     * @param string $field
     * @return bool
     */
    protected function isValidField( string $field ): bool
    {
        return in_array( $field, $this->getFillableFields() );
    }

    /**
     * Retorna lista de campos fillable do modelo.
     *
     * @return array<string>
     */
    protected function getFillableFields(): array
    {
        return $this->model->getFillable();
    }

}
