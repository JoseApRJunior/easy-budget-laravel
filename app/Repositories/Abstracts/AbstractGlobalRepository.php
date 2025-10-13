<?php
declare(strict_types=1);

namespace App\Repositories\Abstracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Repositories\Contracts\GlobalRepositoryInterface;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório abstrato base para operações globais (sem tenant_id).
 *
 * Esta classe fornece funcionalidades avançadas para repositórios que operam
 * em contexto global, sem restrições de tenant. É ideal para entidades que
 * precisam ser acessadas por todos os tenants ou pelo sistema administrativo.
 *
 * Implementa diretamente BaseRepositoryInterface e GlobalRepositoryInterface
 * sem herança desnecessária, promovendo menor acoplamento e maior flexibilidade.
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
abstract class AbstractGlobalRepository implements BaseRepositoryInterface, GlobalRepositoryInterface
{
    use RepositoryFiltersTrait;

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
    // IMPLEMENTAÇÃO DOS MÉTODOS ESPECÍFICOS DO GlobalRepositoryInterface
    // --------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function findGlobal( int $id ): ?Model
    {
        return $this->find( $id );
    }

    /**
     * {@inheritdoc}
     */
    public function getAllGlobal(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        $query = $this->model->newQuery();

        // Aplica filtros usando trait
        $this->applyFilters( $query, $criteria );

        // Aplica ordenação usando trait
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

    /**
     * {@inheritdoc}
     */
    public function createGlobal( array $data ): Model
    {
        return $this->create( $data );
    }

    /**
     * {@inheritdoc}
     */
    public function updateGlobal( int $id, array $data ): ?Model
    {
        return $this->update( $id, $data );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteGlobal( int $id ): bool
    {
        return $this->delete( $id );
    }

    /**
     * {@inheritdoc}
     */
    public function paginateGlobal( int $perPage = 15, array $filters = [] ): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Aplica filtros usando trait
        $this->applyFilters( $query, $filters );

        return $query->paginate( $perPage );
    }

    /**
     * {@inheritdoc}
     */
    public function countGlobal( array $filters = [] ): int
    {
        $query = $this->model->newQuery();

        // Aplica filtros usando trait
        $this->applyFilters( $query, $filters );

        return $query->count();
    }

}
