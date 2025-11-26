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
        $filters    = $request->only(['search', 'active']);
        $hasFilters = collect($filters)->filter(fn($v) => filled($v))->isNotEmpty();
        $confirmAll = (bool) $request->boolean('all');

        if ($hasFilters || $confirmAll) {
            $query = Category::query()->ownedByTenant($tenantId)
                ->with(['tenants' => function ($q) use ($tenantId) {
                    if ($tenantId !== null) {
                        $q->where('tenant_id', $tenantId);
                    }
                }])
                ->with('parent');

            if (filled($filters['search'] ?? null)) {
                $search = trim((string) $filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                })->orWhereHas('parent', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%");
                });
            }

            if (($filters['active'] ?? '') !== '') {
                $active = (string) $filters['active'] === '1';
                $query->where('is_active', $active);
            }

            $categories = $query
                ->leftJoin('categories as parent', 'parent.id', '=', 'categories.parent_id')
                ->select('categories.*')
                ->orderByRaw('COALESCE(parent.name, categories.name) ASC')
                ->orderBy('categories.name', 'ASC')
                ->paginate(15)
                ->appends($request->query());
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
        $tenantId = TenantScoped::getCurrentTenantId() ?? (auth()->user()->tenant_id ?? null);
        $parents = $tenantId !== null
            ? Category::query()->ownedByTenant($tenantId)->orderBy('name')->get(['id', 'name'])
            : collect();
        return view('pages.category.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $slug = Str::slug($validated['name']);

        $tenantId = $request->integer('tenant_id') ?: (TenantScoped::getCurrentTenantId() ?? (auth()->user()->tenant_id ?? null));

        // Se já existir categoria com o mesmo slug
        $existing = Category::query()->where('slug', $slug)->first();
        if ($existing) {
            return back()->withErrors(['name' => 'Este nome já está em uso.']) . withInput();
        }

        // Se informou parent_id, validar que pertence ao mesmo tenant
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
                return back()->withErrors(['parent_id' => 'A categoria pai deve pertencer ao seu espaço.']) . withInput();
            }
        }

        // Caso não exista, cria nova categoria e vincula ao tenant
        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => true,
        ]);

        if ($tenantId !== null) {
            $category->tenants()->syncWithoutDetaching([
                $tenantId => ['is_default' => false, 'is_custom' => true],
            ]);
        }

        return redirect()->route('categories.index');
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
        $tenantId = TenantScoped::getCurrentTenantId() ?? (auth()->user()->tenant_id ?? null);
        $parents = $tenantId !== null
            ? Category::query()->ownedByTenant($tenantId)
            ->where('id', '!=', $id)
            ->orderBy('name')->get(['id', 'name'])
            : collect();
        return view('pages.category.edit', compact('category', 'parents'));
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $category = Category::findOrFail($id);
        $slug = Str::slug($validated['name']);
        if (!Category::validateUniqueSlug($slug, $category->id)) {
            return back()->withErrors(['name' => 'Slug já existe'])->withInput();
        }

        // Validar parent_id do mesmo tenant
        $tenantId = TenantScoped::getCurrentTenantId() ?? (auth()->user()->tenant_id ?? null);
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
                return back()->withErrors(['parent_id' => 'A categoria pai deve pertencer ao seu espaço.']) . withInput();
            }
        }

        $category->name = $validated['name'];
        $category->slug = $slug;
        $category->parent_id = $validated['parent_id'] ?? null;
        $category->save();

        return redirect()->route('categories.index');
    }

    public function destroy(int $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return redirect()->route('categories.index');
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

        return redirect()->route('categories.index')->with('status', 'Categoria definida como padrão com sucesso.');
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
            $category = Category::query()->where('slug', $slug)->first();
            if ($category) {
                $exists = true;
                $id = $category->id;
                if ($tenantId !== null) {
                    $attached = \Illuminate\Support\Facades\DB::table('category_tenant')
                        ->where('tenant_id', $tenantId)
                        ->where('category_id', $category->id)
                        ->exists();
                }
                $editUrl = route('categories.edit', $category->id);
            }
        }

        return response()->json([
            'slug' => $slug,
            'exists' => $exists,
            'attached' => $attached,
            'id' => $id,
            'edit_url' => $editUrl,
        ]);
    }
}
