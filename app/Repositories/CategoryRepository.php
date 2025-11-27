<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Abstracts\AbstractGlobalRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScope;
use App\Models\Traits\TenantScoped;

/**
 * Repositório para gerenciamento de categorias.
 *
 * Estende AbstractGlobalRepository para operações globais
 * (categorias são compartilhadas entre tenants).
 */
class CategoryRepository extends AbstractGlobalRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Category();
    }

    /**
     * Busca categoria por slug.
     *
     * @param string $slug Slug da categoria
     * @return Category|null Categoria encontrada
     */
    public function findBySlug(string $slug): ?Model
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Lista categorias ativas.
     *
     * @param array<string, string>|null $orderBy Ordenação
     * @return Collection<Category> Categorias ativas
     */
    public function listActive(?array $orderBy = null): Collection
    {
        return $this->getAllGlobal(
            ['is_active' => true],
            $orderBy,
        );
    }

    /**
     * Busca categorias ordenadas por nome.
     *
     * @param string $direction Direção da ordenação (asc/desc)
     * @return Collection<Category> Categorias ordenadas
     */
    public function findOrderedByName(string $direction = 'asc'): Collection
    {
        return $this->getAllGlobal(
            [],
            ['name' => $direction],
        );
    }

    /**
     * Lista categorias do tenant atual junto com categorias globais (tenant_id NULL).
     *
     * @param array<string, string>|null $orderBy
     * @return Collection<Category>
     */
    public function listWithGlobals(?array $orderBy = null): Collection
    {
        $tenantId = TenantScoped::getCurrentTenantId();
        $query = $this->model->newQuery()->forTenantWithGlobals($tenantId);
        $this->applyOrderBy($query, $orderBy);
        return $query->get();
    }

    /**
     * Pagina categorias do tenant atual junto com categorias globais (sem vínculo de tenant).
     *
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @param array<string, string>|null $orderBy
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateWithGlobals(int $perPage = 10, array $filters = [], ?array $orderBy = ['name' => 'asc']): \Illuminate\Pagination\LengthAwarePaginator
    {
        $tenantId = TenantScoped::getCurrentTenantId();
        $query = $this->model->newQuery()->forTenantWithGlobals($tenantId);
        $this->applyFilters($query, $filters);
        $this->applyOrderBy($query, $orderBy);
        return $query->paginate($perPage);
    }
}
