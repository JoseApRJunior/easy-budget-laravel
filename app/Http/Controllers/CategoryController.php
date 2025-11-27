<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Category;
use App\Models\Traits\TenantScoped;
use App\Repositories\CategoryRepository;
use App\Exports\CategoriesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(private CategoryRepository $repository) {}

    public function index(Request $request)
    {
        $tenantId   = TenantScoped::getCurrentTenantId();
        if ($tenantId === null) {
            $tenantId = (int) (auth()->user()->tenant_id ?? 0) ?: null;
        }
        $filters    = $request->only(['search', 'active', 'per_page']);
        $hasFilters = collect($filters)->filter(fn($v) => filled($v))->isNotEmpty();
        $confirmAll = $request->has('all') && in_array((string) $request->input('all'), ['1', 'true', 'on', 'yes'], true);
        $perPage = (int) ($filters['per_page'] ?? $request->input('per_page', 10));
        $allowedPerPage = [10, 20, 50];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        if ($hasFilters || $confirmAll) {
            $serviceFilters = [
                'search' => $filters['search'] ?? '',
                'active' => $filters['active'] ?? '',
            ];
            $result = app(\App\Services\Domain\CategoryService::class)->paginateWithGlobals($serviceFilters, $perPage);
            $categories = $this->getServiceData($result, collect());
            if (method_exists($categories, 'appends')) {
                $categories = $categories->appends($request->query());
            }
        } else {
            $categories = collect();
        }

        return view('pages.category.index', [
            'categories' => $categories,
            'filters'    => $filters,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $isAdmin = $user ? app(\App\Services\Core\PermissionService::class)->canManageGlobalCategories($user) : false;
        if ($isAdmin) {
            $parents = Category::query()->whereNull('tenant_id')->orderBy('name')->get(['id', 'name']);
        } else {
            $tenantId = TenantScoped::getCurrentTenantId() ?? ($user->tenant_id ?? null);
            $parents = $tenantId !== null
                ? Category::query()->ownedByTenant($tenantId)->orderBy('name')->get(['id', 'name'])
                : collect();
        }
        $defaults = ['is_active' => true];
        return view('pages.category.create', compact('parents', 'defaults'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage-custom-categories');
        $validated = $this->validateRequest($request, [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $slug = Str::slug($validated['name']);
        $user = auth()->user();
        $isAdmin = $user ? app(\App\Services\Core\PermissionService::class)->canManageGlobalCategories($user) : false;
        $tenantId = $isAdmin ? null : ($request->integer('tenant_id') ?: (TenantScoped::getCurrentTenantId() ?? ($user->tenant_id ?? null)));

        if ($isAdmin) {
            $exists = Category::query()->where('slug', $slug)->exists();
            if ($exists) {
                return back()->withErrors(['name' => 'Este nome já está em uso.'])->withInput();
            }
            if (($validated['parent_id'] ?? null) !== null) {
                $parentId = (int) $validated['parent_id'];
                $isBaseParent = Category::query()->where('id', $parentId)->whereNull('tenant_id')->exists();
                if (!$isBaseParent) {
                    return back()->withErrors(['parent_id' => 'Admin só pode selecionar categoria pai base.'])->withInput();
                }
            }
            $category = Category::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'parent_id' => $validated['parent_id'] ?? null,
                'tenant_id' => null,
                'is_active' => true,
            ]);
            $tenantIds = \App\Models\Tenant::query()->pluck('id')->all();
            $attach = [];
            foreach ($tenantIds as $tid) {
                $attach[$tid] = ['is_default' => false, 'is_custom' => false];
            }
            $category->tenants()->syncWithoutDetaching($attach);
        } else {
            $existingInTenant = Category::query()
                ->where('slug', $slug)
                ->where(function ($q) use ($tenantId) {
                    if ($tenantId !== null) {
                        $q->where('tenant_id', $tenantId)
                            ->orWhereExists(function ($sub) use ($tenantId) {
                                $sub->selectRaw(1)
                                    ->from('category_tenant')
                                    ->whereColumn('category_tenant.category_id', 'categories.id')
                                    ->where('category_tenant.tenant_id', $tenantId);
                            });
                    } else {
                        $q->whereRaw('1=0');
                    }
                })
                ->exists();
            if ($existingInTenant) {
                return back()->withErrors(['name' => 'Este nome já está em uso.'])->withInput();
            }
            if (($validated['parent_id'] ?? null) !== null) {
                if ($tenantId === null) {
                    return back()->withErrors(['parent_id' => 'Selecione uma categoria pai disponível.'])->withInput();
                }
                $parentId = (int) $validated['parent_id'];
                $parentAttached = \Illuminate\Support\Facades\DB::table('category_tenant')
                    ->where('tenant_id', $tenantId)
                    ->where('category_id', $parentId)
                    ->exists();
                if (!$parentAttached) {
                    return back()->withErrors(['parent_id' => 'A categoria pai deve pertencer ao seu espaço.'])->withInput();
                }
            }
            $category = Category::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'parent_id' => $validated['parent_id'] ?? null,
                'tenant_id' => $tenantId,
                'is_active' => true,
            ]);
            if ($tenantId !== null) {
                $category->tenants()->syncWithoutDetaching([
                    $tenantId => ['is_default' => false, 'is_custom' => true],
                ]);
            }
        }

        \App\Models\AuditLog::log(
            'created',
            $category,
            [],
            $category->only(['id', 'name', 'slug', 'parent_id', 'tenant_id', 'is_active']),
            ['context' => 'categories_store']
        );

        $this->logOperation('categories_store', ['id' => $category->id, 'name' => $category->name]);
        return $this->redirectSuccess('categories.index', 'Categoria criada com sucesso.');
    }

    public function show(string $slug)
    {
        $tenantId = TenantScoped::getCurrentTenantId();
        $category = $this->repository->findBySlug($slug);
        abort_unless($category, 404);
        $category->load(['tenants' => function ($q) use ($tenantId) {
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
        if ($isAdmin) {
            $parents = Category::query()->whereNull('tenant_id')
                ->where('id', '!=', $id)
                ->orderBy('name')->get(['id', 'name']);
        } else {
            $tenantId = TenantScoped::getCurrentTenantId() ?? ($user->tenant_id ?? null);
            $parents = $tenantId !== null
                ? Category::query()->ownedByTenant($tenantId)
                ->where('id', '!=', $id)
                ->orderBy('name')->get(['id', 'name'])
                : collect();
        }
        return view('pages.category.edit', compact('category', 'parents'));
    }

    public function update(Request $request, int $id)
    {
        $this->authorize('manage-custom-categories');
        $validated = $this->validateRequest($request, [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $category = Category::findOrFail($id);
        $user = auth()->user();
        $isAdmin = $user && method_exists($user, 'isAdmin') && $user->isAdmin();
        if (!$isAdmin && $category->tenant_id === null) {
            return back()->withErrors(['category' => 'Categorias globais só podem ser editadas por administradores.']);
        }
        $slug = Str::slug($validated['name']);
        if (!Category::validateUniqueSlug($slug, $category->id)) {
            return back()->withErrors(['name' => 'Slug já existe'])->withInput();
        }
        $tenantId = TenantScoped::getCurrentTenantId() ?? ($user->tenant_id ?? null);
        if (($validated['parent_id'] ?? null) !== null) {
            $parentId = (int) $validated['parent_id'];
            if ($isAdmin) {
                $isBaseParent = Category::query()->where('id', $parentId)->whereNull('tenant_id')->exists();
                if (!$isBaseParent) {
                    return back()->withErrors(['parent_id' => 'Admin só pode selecionar categoria pai base.'])->withInput();
                }
            } else {
                if ($tenantId === null) {
                    return back()->withErrors(['parent_id' => 'Selecione uma categoria pai disponível.'])->withInput();
                }
                $parentAttached = \Illuminate\Support\Facades\DB::table('category_tenant')
                    ->where('tenant_id', $tenantId)
                    ->where('category_id', $parentId)
                    ->exists();
                if (!$parentAttached) {
                    return back()->withErrors(['parent_id' => 'A categoria pai deve pertencer ao seu espaço.'])->withInput();
                }
            }
        }

        $category->name = $validated['name'];
        $category->slug = $slug;
        $category->parent_id = $validated['parent_id'] ?? null;
        $before = $category->only(['id', 'name', 'slug', 'parent_id', 'tenant_id', 'is_active']);
        $category->save();
        \App\Models\AuditLog::log(
            'updated',
            $category,
            $before,
            $category->only(['id', 'name', 'slug', 'parent_id', 'tenant_id', 'is_active']),
            ['context' => 'categories_update']
        );

        $this->logOperation('categories_update', ['id' => $category->id, 'name' => $category->name]);
        return $this->redirectSuccess('categories.index', 'Categoria atualizada com sucesso.');
    }

    public function destroy(int $id)
    {
        $this->authorize('manage-custom-categories');
        $category = Category::findOrFail($id);
        $user = auth()->user();
        $isAdmin = $user && method_exists($user, 'isAdmin') && $user->isAdmin();
        if (!$isAdmin && $category->tenant_id === null) {
            return $this->redirectError('categories.index', 'Categorias globais só podem ser excluídas por administradores.');
        }
        if ($category->services()->exists()) {
            return $this->redirectError('categories.index', 'Não é possível excluir: possui serviços associados');
        }
        $before = $category->only(['id', 'name', 'slug', 'parent_id', 'tenant_id', 'is_active']);
        $category->delete();
        \App\Models\AuditLog::log(
            'deleted',
            $category,
            $before,
            [],
            ['context' => 'categories_destroy']
        );
        $this->logOperation('categories_destroy', ['id' => $id]);
        return $this->redirectSuccess('categories.index', 'Categoria excluída com sucesso.');
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx');

        $fileName = match ($format) {
            'csv'  => 'categories.csv',
            'xlsx' => 'categories.xlsx',
            default => 'categories.xlsx',
        };

        return Excel::download(new CategoriesExport(), $fileName);
    }

    public function setDefault(Request $request, int $id)
    {
        $this->authorize('manage-custom-categories');
        $category = Category::findOrFail($id);
        $tenantId = TenantScoped::getCurrentTenantId();
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

        $oldDefault = \Illuminate\Support\Facades\DB::table('category_tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->first();

        $category->tenants()->syncWithoutDetaching([
            $tenantId => ['is_default' => true, 'is_custom' => false],
        ]);

        \App\Models\AuditLog::log(
            'updated',
            $category,
            ['default_category_id' => $oldDefault->category_id ?? null],
            ['default_category_id' => $category->id],
            ['context' => 'set_default_category']
        );

        $this->logOperation('categories_set_default', ['id' => $category->id, 'tenant_id' => $tenantId]);
        return $this->redirectSuccess('categories.index', 'Categoria definida como padrão com sucesso.');
    }

    public function checkSlug(Request $request)
    {
        $slugInput = (string) $request->get('slug', '');
        $slug = Str::slug($slugInput);
        $tenantId = $request->integer('tenant_id') ?: (TenantScoped::getCurrentTenantId() ?? (auth()->user()->tenant_id ?? null));

        $exists = false;
        $attached = false;
        $id = null;
        $editUrl = null;

        if ($slug !== '') {
            $exists = Category::query()
                ->where('slug', $slug)
                ->where(function ($q) use ($tenantId) {
                    if ($tenantId !== null) {
                        $q->where('tenant_id', $tenantId)
                            ->orWhereExists(function ($sub) use ($tenantId) {
                                $sub->selectRaw(1)
                                    ->from('category_tenant')
                                    ->whereColumn('category_tenant.category_id', 'categories.id')
                                    ->where('category_tenant.tenant_id', $tenantId);
                            });
                    } else {
                        $q->whereRaw('1=0');
                    }
                })
                ->exists();
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
