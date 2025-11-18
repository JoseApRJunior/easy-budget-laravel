<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Category;
use App\Models\Tenant;
use App\Services\Shared\CacheService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CategoriesExport;

class CategoryManagementController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(Request $request): View
    {
        $this->authorize('manage-categories');

        $search = $request->get('search');
        $type = $request->get('type');
        $status = $request->get('status', 'all');
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $perPage = $request->get('per_page', 25);

        $query = Category::with(['tenant', 'parent'])
            ->withCount(['children', 'activities']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        $categories = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->appends($request->query());

        $categoryTypes = Category::select('type')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values();

        $statistics = $this->getCategoryStatistics();

        return view('admin.categories.index', compact(
            'categories',
            'search',
            'type',
            'status',
            'sortBy',
            'sortOrder',
            'categoryTypes',
            'statistics'
        ));
    }

    public function create(): View
    {
        $this->authorize('manage-categories');

        $parentCategories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $tenants = Tenant::where('is_active', true)
            ->orderBy('name')
            ->get();

        $categoryTypes = $this->getCategoryTypes();

        return view('admin.categories.create', compact(
            'parentCategories',
            'tenants',
            'categoryTypes'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-categories');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|max:50',
            'parent_id' => 'nullable|exists:categories,id',
            'tenant_id' => 'nullable|exists:tenants,id',
            'is_active' => 'boolean',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'order' => 'integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $category = new Category();
            $category->fill($validated);
            $category->slug = $this->generateUniqueSlug($validated['name']);
            $category->save();

            $this->cacheService->forgetPattern('categories.*');

            DB::commit();

            Log::info('Category created', [
                'category_id' => $category->id,
                'name' => $category->name,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.categories.index')
                ->with('success', 'Categoria criada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating category', [
                'error' => $e->getMessage(),
                'data' => $validated,
                'admin_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Erro ao criar categoria. Por favor, tente novamente.');
        }
    }

    public function show(Category $category): View
    {
        $this->authorize('manage-categories');

        $category->load([
            'tenant',
            'parent',
            'children',
            'activities',
            'activities.tenant'
        ]);

        $statistics = $this->getCategoryDetailedStatistics($category);
        $recentActivities = $this->getRecentCategoryActivities($category);

        return view('admin.categories.show', compact(
            'category',
            'statistics',
            'recentActivities'
        ));
    }

    public function edit(Category $category): View
    {
        $this->authorize('manage-categories');

        $category->load(['parent', 'tenant']);

        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $tenants = Tenant::where('is_active', true)
            ->orderBy('name')
            ->get();

        $categoryTypes = $this->getCategoryTypes();

        return view('admin.categories.edit', compact(
            'category',
            'parentCategories',
            'tenants',
            'categoryTypes'
        ));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('manage-categories');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|max:50',
            'parent_id' => 'nullable|exists:categories,id',
            'tenant_id' => 'nullable|exists:tenants,id',
            'is_active' => 'boolean',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'order' => 'integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            if ($category->name !== $validated['name']) {
                $category->slug = $this->generateUniqueSlug($validated['name'], $category->id);
            }

            $category->fill($validated);
            $category->save();

            $this->cacheService->forgetPattern('categories.*');

            DB::commit();

            Log::info('Category updated', [
                'category_id' => $category->id,
                'name' => $category->name,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.categories.show', $category)
                ->with('success', 'Categoria atualizada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating category', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
                'data' => $validated,
                'admin_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Erro ao atualizar categoria. Por favor, tente novamente.');
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('manage-categories');

        if ($category->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir uma categoria que possui subcategorias.'
            ], 422);
        }

        if ($category->activities()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir uma categoria que possui atividades associadas.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $categoryName = $category->name;
            $category->delete();

            $this->cacheService->forgetPattern('categories.*');

            DB::commit();

            Log::info('Category deleted', [
                'category_name' => $categoryName,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoria excluída com sucesso!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting category', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir categoria. Por favor, tente novamente.'
            ], 500);
        }
    }

    public function toggleStatus(Category $category): JsonResponse
    {
        $this->authorize('manage-categories');

        try {
            $category->is_active = !$category->is_active;
            $category->save();

            $this->cacheService->forgetPattern('categories.*');

            Log::info('Category status toggled', [
                'category_id' => $category->id,
                'name' => $category->name,
                'new_status' => $category->is_active ? 'active' : 'inactive',
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status alterado com sucesso!',
                'is_active' => $category->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling category status', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status. Por favor, tente novamente.'
            ], 500);
        }
    }

    public function duplicate(Category $category): RedirectResponse
    {
        $this->authorize('manage-categories');

        try {
            DB::beginTransaction();

            $newCategory = $category->replicate();
            $newCategory->name = $category->name . ' (Cópia)';
            $newCategory->slug = $this->generateUniqueSlug($newCategory->name);
            $newCategory->is_active = false;
            $newCategory->save();

            $this->cacheService->forgetPattern('categories.*');

            DB::commit();

            Log::info('Category duplicated', [
                'original_category_id' => $category->id,
                'new_category_id' => $newCategory->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.categories.edit', $newCategory)
                ->with('success', 'Categoria duplicada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error duplicating category', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return back()->with('error', 'Erro ao duplicar categoria. Por favor, tente novamente.');
        }
    }

    public function export(Request $request)
    {
        $this->authorize('manage-categories');

        $format = $request->get('format', 'xlsx');
        $search = $request->get('search');
        $type = $request->get('type');
        $status = $request->get('status');

        $query = Category::with(['tenant', 'parent'])
            ->withCount(['children', 'activities']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status && $status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        $categories = $query->orderBy('name')->get();

        Log::info('Categories exported', [
            'format' => $format,
            'count' => $categories->count(),
            'admin_id' => auth()->id(),
        ]);

        return Excel::download(
            new CategoriesExport($categories),
            'categorias_' . now()->format('Y-m-d_H-i-s') . '.' . $format
        );
    }

    public function getSubcategories(Request $request): JsonResponse
    {
        $this->authorize('manage-categories');

        $parentId = $request->get('parent_id');
        $excludeId = $request->get('exclude_id');

        $query = Category::where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $subcategories = $query->get(['id', 'name', 'slug']);

        return response()->json($subcategories);
    }

    protected function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $baseSlug = $slug;
        $counter = 1;

        while (true) {
            $query = Category::where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function getCategoryStatistics(): array
    {
        return Cache::remember('admin.categories.statistics', 300, function () {
            return [
                'total' => Category::count(),
                'active' => Category::where('is_active', true)->count(),
                'inactive' => Category::where('is_active', false)->count(),
                'with_children' => Category::has('children')->count(),
                'with_activities' => Category::has('activities')->count(),
                'by_type' => Category::select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
            ];
        });
    }

    protected function getCategoryDetailedStatistics(Category $category): array
    {
        return [
            'total_activities' => $category->activities()->count(),
            'active_activities' => $category->activities()->where('is_active', true)->count(),
            'total_children' => $category->children()->count(),
            'active_children' => $category->children()->where('is_active', true)->count(),
            'usage_by_tenant' => $category->activities()
                ->select('tenant_id', DB::raw('count(*) as count'))
                ->groupBy('tenant_id')
                ->with('tenant:id,name')
                ->get(),
            'recent_activities' => $category->activities()
                ->with('tenant:id,name')
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }

    protected function getRecentCategoryActivities(Category $category)
    {
        return $category->activities()
            ->with(['tenant', 'category'])
            ->latest()
            ->limit(10)
            ->get();
    }

    protected function getCategoryTypes(): array
    {
        return [
            'product' => 'Produto',
            'service' => 'Serviço',
            'expense' => 'Despesa',
            'income' => 'Receita',
            'asset' => 'Ativo',
            'liability' => 'Passivo',
            'equity' => 'Patrimônio Líquido',
            'other' => 'Outro',
        ];
    }
}
