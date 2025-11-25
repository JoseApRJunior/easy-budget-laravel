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
            $query = Category::query()->forTenantWithGlobals($tenantId)
                ->with(['tenants' => function ($q) use ($tenantId) {
                    if ($tenantId !== null) {
                        $q->where('tenant_id', $tenantId);
                    }
                }]);

            if (filled($filters['search'] ?? null)) {
                $search = trim((string) $filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            }

            if (($filters['active'] ?? '') !== '') {
                $active = (string) $filters['active'] === '1';
                $query->where('is_active', $active);
            }

            $categories = $query->orderBy('name')
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
        return view('pages.category.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $slug = Str::slug($validated['name']);

        $tenantId = TenantScoped::getCurrentTenantId();

        // Se já existir categoria com o mesmo slug
        $existing = Category::query()->where('slug', $slug)->first();
        if ($existing) {
            if ($tenantId !== null) {
                $alreadyAttached = $existing->tenants()->where('tenant_id', $tenantId)->exists();
                if ($alreadyAttached) {
                    return back()->withErrors(['name' => 'Já existe uma categoria com este slug neste tenant.'])->withInput();
                }

                // Vincula a categoria existente ao tenant atual como custom
                $existing->tenants()->syncWithoutDetaching([
                    $tenantId => ['is_default' => false, 'is_custom' => true],
                ]);
            }

            return redirect()->route('categories.index');
        }

        // Caso não exista, cria nova categoria e vincula ao tenant
        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
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
        return view('pages.category.edit', compact('category'));
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = Category::findOrFail($id);
        $slug = Str::slug($validated['name']);
        if (!Category::validateUniqueSlug($slug, $category->id)) {
            return back()->withErrors(['name' => 'Slug já existe'])->withInput();
        }

        $category->name = $validated['name'];
        $category->slug = $slug;
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
        $tenantId = TenantScoped::getCurrentTenantId();

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
                    $attached = $category->tenants()->where('tenant_id', $tenantId)->exists();
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
