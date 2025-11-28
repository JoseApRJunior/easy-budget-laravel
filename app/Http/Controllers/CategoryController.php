<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\CategoriesExport;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Repositories\CategoryRepository;

use App\Services\Core\PermissionService;
use App\Services\Domain\CategoryManagementService;
use App\Services\Domain\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepository $repository,
        private CategoryManagementService $managementService
    ) {}

    public function index(Request $request)
    {
        $tenantId = TenantScoped::getCurrentTenantId();
        if ($tenantId === null) {
            $tenantId = (int) (auth()->user()->tenant_id ?? 0) ?: null;
        }
        $filters = $request->only(['search', 'active', 'per_page']);
        $hasFilters = collect($filters)->filter(fn($v) => filled($v))->isNotEmpty();
        $confirmAll = $request->has('all') && in_array((string) $request->input('all'), ['1', 'true', 'on', 'yes'], true);
        $perPage = (int) ($filters['per_page'] ?? $request->input('per_page', 10));
        $allowedPerPage = [10, 20, 50];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $user = auth()->user();
        $isAdmin = $user ? app(PermissionService::class)->canManageGlobalCategories($user) : false;

        $serviceFilters = [
            'search' => $filters['search'] ?? '',
            'active' => $filters['active'] ?? '',
        ];
        $service = app(CategoryService::class);

        if ($isAdmin) {
            $result = $service->paginateGlobalOnly($serviceFilters, $perPage);
            $categories = $this->getServiceData($result, collect());
            if (method_exists($categories, 'appends')) {
                $categories = $categories->appends($request->query());
            }
        } else {
            if (! $hasFilters) {
                $confirmAll = true;
            }

            if ($hasFilters || $confirmAll) {
                $result = $service->paginateWithGlobals($serviceFilters, $perPage);
                $categories = $this->getServiceData($result, collect());
                if (method_exists($categories, 'appends')) {
                    $categories = $categories->appends($request->query());
                }

                // Fallback: se não houver resultados, tenta globais somente
                if (method_exists($categories, 'total') && (int) $categories->total() === 0) {
                    $result = $service->paginateGlobalOnly($serviceFilters, $perPage);
                    $categories = $this->getServiceData($result, collect());
                    if (method_exists($categories, 'appends')) {
                        $categories = $categories->appends($request->query());
                    }
                }
            } else {
                $categories = collect();
            }
        }

        return view('pages.category.index', [
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $isAdmin = $user ? app(\App\Services\Core\PermissionService::class)->canManageGlobalCategories($user) : false;
        if ($isAdmin) {
            $parents = Category::query()
                ->globalOnly()
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            $tenantId = TenantScoped::getCurrentTenantId() ?? ($user->tenant_id ?? null);
            $parents = $tenantId !== null
                ? Category::query()
                ->forTenant($tenantId)
                ->where(function ($q) use ($tenantId) {
                    $q->where('is_active', true)
                        ->orWhereHas('tenants', function ($t) use ($tenantId) {
                            $t->where('tenant_id', $tenantId)
                                ->where('is_custom', true);
                        });
                })
                ->orderBy('name')
                ->get(['id', 'name'])
                : collect();
        }
        $defaults = ['is_active' => true];

        return view('pages.category.create', compact('parents', 'defaults'));
    }

    public function store(StoreCategoryRequest $request)
    {
        $user = auth()->user();
        $isAdmin = $user ? app(\App\Services\Core\PermissionService::class)->canManageGlobalCategories($user) : false;
        $tenantId = $isAdmin ? null : (TenantScoped::getCurrentTenantId() ?? ($user->tenant_id ?? null));

        $result = $this->managementService->createCategory($request->validated(), $tenantId);

        if ($result->isError()) {
            return back()->with('error', $result->getMessage())->withInput();
        }

        $category = $result->getData();
        $this->logOperation('categories_store', ['id' => $category->id, 'name' => $category->name]);

        return $this->redirectSuccess('categories.index', 'Categoria criada com sucesso.');
    }

    public function show(string $slug)
    {
        $tenantId = auth()->user()->tenant_id ?? null;
        $category = $this->repository->findBySlug($slug);
        abort_unless($category, 404);
        $category->load(['parent', 'tenants' => function ($q) use ($tenantId) {
            if ($tenantId !== null) {
                $q->where('tenant_id', $tenantId);
            }
        }]);

        return view('pages.category.show', compact('category'));
    }

    public function edit(int $id)
    {
        $category = Category::findOrFail($id);
        $user = auth()->user();
        $isAdmin = $user ? app(\App\Services\Core\PermissionService::class)->canManageGlobalCategories($user) : false;
        if ($isAdmin && !$category->isGlobal()) {
            return $this->redirectError('categories.index', 'Admin só pode editar categorias globais.');
        }
        if ($isAdmin) {
            $parents = Category::query()
                ->globalOnly()
                ->where('id', '!=', $id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            $tenantId = $user->tenant_id ?? null;
            $parents = $tenantId !== null
                ? Category::query()
                ->forTenant($tenantId)
                ->where('id', '!=', $id)
                ->where(function ($q) use ($tenantId) {
                    $q->where('is_active', true)
                        ->orWhereHas('tenants', function ($t) use ($tenantId) {
                            $t->where('tenant_id', $tenantId)
                                ->where('is_custom', true);
                        });
                })
                ->orderBy('name')
                ->get(['id', 'name'])
                : collect();
        }

        return view('pages.category.edit', compact('category', 'parents'));
    }

    public function update(UpdateCategoryRequest $request, int $id)
    {
        $category = Category::findOrFail($id);

        $result = $this->managementService->updateCategory($category, $request->validated());

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()])->withInput();
        }

        $this->logOperation('categories_update', ['id' => $category->id, 'name' => $category->name]);

        return $this->redirectSuccess('categories.index', 'Categoria atualizada com sucesso.');
    }

    public function destroy(int $id)
    {
        $this->authorize('manage-custom-categories');
        $category = Category::findOrFail($id);

        $result = $this->managementService->deleteCategory($category);

        if ($result->isError()) {
            return $this->redirectError('categories.index', $result->getMessage());
        }

        $this->logOperation('categories_destroy', ['id' => $id]);

        return $this->redirectSuccess('categories.index', 'Categoria excluída com sucesso.');
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx');

        $fileName = match ($format) {
            'csv' => 'categories.csv',
            'xlsx' => 'categories.xlsx',
            default => 'categories.xlsx',
        };

        $user = auth()->user();
        $isAdmin = $user ? app(\App\Services\Core\PermissionService::class)->canManageGlobalCategories($user) : false;
        if ($isAdmin) {
            $categories = Category::query()
                ->globalOnly()
                ->with('parent')
                ->orderBy('name')
                ->get();
        } else {
            $tenantId = TenantScoped::getCurrentTenantId() ?? ($user->tenant_id ?? null);
            $categories = $tenantId !== null
                ? Category::query()
                ->forTenant($tenantId)
                ->where(function ($q) use ($tenantId) {
                    $q->where('is_active', true)
                        ->orWhereHas('tenants', function ($t) use ($tenantId) {
                            $t->where('tenant_id', $tenantId)
                                ->where('is_custom', true);
                        });
                })
                ->with('parent')
                ->orderBy('name')
                ->get()
                : collect();
        }

        return Excel::download(new CategoriesExport($categories), $fileName);
    }

    public function setDefault(Request $request, int $id)
    {
        $this->authorize('manage-custom-categories');
        $category = Category::findOrFail($id);
        $tenantId = auth()->user()->tenant_id ?? null;
        $user = auth()->user();

        if ($user && $user->isAdmin() && $request->filled('tenant_id')) {
            $this->authorize('manage-global-categories');
            $tenantCandidate = (int) $request->input('tenant_id');
            if ($tenantCandidate > 0) {
                $exists = \App\Models\Tenant::query()->where('id', $tenantCandidate)->exists();
                if ($exists) {
                    $tenantId = $tenantCandidate;
                }
            }
        }

        if ($tenantId === null) {
            return redirect()->route('categories.index')->with('status', 'Não foi possível determinar o tenant.');
        }

        $result = $this->managementService->setDefaultCategory($category, $tenantId);

        if ($result->isError()) {
            return $this->redirectError('categories.index', $result->getMessage());
        }

        $this->logOperation('categories_set_default', ['id' => $category->id, 'tenant_id' => $tenantId]);

        return $this->redirectSuccess('categories.index', 'Categoria definida como padrão com sucesso.');
    }

    public function checkSlug(Request $request)
    {
        $slugInput = (string) $request->get('slug', '');
        $slug = Str::slug($slugInput);
        $tenantId = $request->integer('tenant_id') ?: (auth()->user()->tenant_id ?? null);

        $exists = false;
        $attached = false;
        $id = null;
        $editUrl = null;

        if ($slug !== '') {
            $query = Category::where('slug', $slug);

            if ($tenantId !== null) {
                // Se for tenant, conflita se existir global ou vinculada a ele
                $query->where(function ($q) use ($tenantId) {
                    $q->globalOnly()
                        ->orWhereHas('tenants', fn($t) => $t->where('tenant_id', $tenantId));
                });
            }
            // Se for admin (tenantId null), conflita se existir qualquer uma com esse slug?
            // A lógica anterior do store para admin era: Category::query()->where('slug', $slug)->exists();
            // Então para admin, qualquer slug duplicado é conflito.

            $exists = $query->exists();
        }

        $this->logOperation('categories_check_slug', ['slug' => $slug, 'exists' => $exists]);

        return $this->jsonSuccess([
            'slug' => $slug,
            'exists' => $exists,
            'attached' => $attached,
            'id' => $id,
            'edit_url' => $editUrl,
        ]);
    }
}
