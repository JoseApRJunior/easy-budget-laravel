<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Category;
use App\Models\Traits\TenantScoped;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(private CategoryRepository $repository) {}

    public function index(Request $request)
    {
        $tenantId = TenantScoped::getCurrentTenantId();
        $categories = Category::query()
            ->forTenantWithGlobals($tenantId)
            ->with(['tenants' => function ($q) use ($tenantId) {
                if ($tenantId !== null) {
                    $q->where('tenant_id', $tenantId);
                }
            }])
            ->orderBy('name')
            ->paginate(15)
            ->appends($request->query());
        return view('pages.category.index', compact('categories'));
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
        if (!Category::validateUniqueSlug($slug)) {
            return back()->withErrors(['name' => 'Slug já existe'])->withInput();
        }

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'is_active' => true,
        ]);

        $tenantId = TenantScoped::getCurrentTenantId();
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
}
