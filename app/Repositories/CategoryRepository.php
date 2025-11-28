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
        $query = $this->model->newQuery()->forTenant($tenantId);  // Scope atualizado
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
    public function paginateWithGlobals(int $perPage = 15, array $filters = [], ?array $orderBy = ['name' => 'asc']): \Illuminate\Pagination\LengthAwarePaginator
    {
        $tenantId = TenantScoped::getCurrentTenantId();

        // Usar novo scope simplificado do model
        $query = $this->model->newQuery()
            ->forTenant($tenantId)  // Scope atualizado
            ->leftJoin('categories as parent', 'parent.id', '=', 'categories.parent_id')
            ->select('categories.*')
            ->orderByRaw('CASE WHEN categories.parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw('COALESCE(parent.name, categories.name) ASC')
            ->orderBy('categories.name', 'ASC');

        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];
            unset($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('categories.name', 'like', "%{$search}%")
                    ->orWhere('categories.slug', 'like', "%{$search}%")
                    ->orWhere('parent.name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['name']) && is_array($filters['name']) && isset($filters['name']['operator'], $filters['name']['value'])) {
            $op = $filters['name']['operator'];
            $val = $filters['name']['value'];
            unset($filters['name']);
            $query->where(function ($q) use ($op, $val) {
                $q->where('categories.name', $op, $val)
                    ->orWhere('parent.name', $op, $val);
            });
        }

        if (!empty($filters['slug']) && is_array($filters['slug']) && isset($filters['slug']['operator'], $filters['slug']['value'])) {
            $op = $filters['slug']['operator'];
            $val = $filters['slug']['value'];
            unset($filters['slug']);
            $query->where('categories.slug', $op, $val);
        }

        if (array_key_exists('is_active', $filters)) {
            $val = $filters['is_active'];
            unset($filters['is_active']);
            $query->where('categories.is_active', $val);
        }

        if (array_key_exists('active', $filters) && $filters['active'] !== '') {
            $val = $filters['active'];
            unset($filters['active']);
            $bool = in_array((string) $val, ['1', 'true', 'on'], true);
            $query->where('categories.is_active', $bool);
        }

        unset($filters['per_page']);

        // Cache Key Generation
        $globalVersion = \Illuminate\Support\Facades\Cache::get('global_categories_version', 1);
        $tenantVersion = $tenantId ? \Illuminate\Support\Facades\Cache::get("tenant_{$tenantId}_categories_version", 1) : 0;

        $cacheKey = "categories_list_tenant_{$tenantId}_v{$tenantVersion}_gv{$globalVersion}_p{$perPage}_" . md5(json_encode($filters) . json_encode($orderBy));

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($query, $filters, $perPage) {
            $this->applyFilters($query, $filters);
            return $query->paginate($perPage);
        });
    }

    /**
     * Pagina apenas categorias globais (tenant_id NULL), ignorando filtros de tenant.
     *
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @param array<string, string>|null $orderBy
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateOnlyGlobals(int $perPage = 15, array $filters = [], ?array $orderBy = ['name' => 'asc']): \Illuminate\Pagination\LengthAwarePaginator
    {
        // Usar novo scope globalOnly() do model
        $query = $this->model->newQuery()
            ->globalOnly()  // Categorias sem vínculo em category_tenant
            ->leftJoin('categories as parent', 'parent.id', '=', 'categories.parent_id')
            ->select('categories.*')
            ->orderByRaw('CASE WHEN categories.parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw('COALESCE(parent.name, categories.name) ASC')
            ->orderBy('categories.name', 'ASC');

        unset($filters['tenant_id']);  // Remover filtro tenant_id se presente

        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];
            unset($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('categories.name', 'like', "%{$search}%")
                    ->orWhere('categories.slug', 'like', "%{$search}%")
                    ->orWhere('parent.name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['name']) && is_array($filters['name']) && isset($filters['name']['operator'], $filters['name']['value'])) {
            $op = $filters['name']['operator'];
            $val = $filters['name']['value'];
            unset($filters['name']);
            $query->where(function ($q) use ($op, $val) {
                $q->where('categories.name', $op, $val)
                    ->orWhere('parent.name', $op, $val);
            });
        }

        if (!empty($filters['slug']) && is_array($filters['slug']) && isset($filters['slug']['operator'], $filters['slug']['value'])) {
            $op = $filters['slug']['operator'];
            $val = $filters['slug']['value'];
            unset($filters['slug']);
            $query->where('categories.slug', $op, $val);
        }

        if (array_key_exists('is_active', $filters)) {
            $val = $filters['is_active'];
            unset($filters['is_active']);
            $query->where('categories.is_active', $val);
        }

        if (array_key_exists('active', $filters) && $filters['active'] !== '') {
            $val = $filters['active'];
            unset($filters['active']);
            $bool = in_array((string) $val, ['1', 'true', 'on'], true);
            $query->where('categories.is_active', $bool);
        }

        unset($filters['per_page']);

        // Cache Key Generation (Only Global Version matters here)
        $globalVersion = \Illuminate\Support\Facades\Cache::get('global_categories_version', 1);

        $cacheKey = "categories_list_global_v{$globalVersion}_p{$perPage}_" . md5(json_encode($filters) . json_encode($orderBy));

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($query, $filters, $perPage) {
            $this->applyFilters($query, $filters);
            return $query->paginate($perPage);
        });
    }
}
