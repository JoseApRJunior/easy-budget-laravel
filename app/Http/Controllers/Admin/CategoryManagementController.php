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
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $perPage = $request->get('per_page', 25);

        $query = Category::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $categories = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->authorize('manage-categories');
        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-categories');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
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
        return view('admin.categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        $this->authorize('manage-categories');
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('manage-categories');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
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

        // Minimal schema: no dependency checks required

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
        return response()->json([
            'success' => false,
            'message' => 'Alteração de status indisponível no esquema mínimo de categorias.'
        ], 422);
    }

    public function duplicate(Category $category): RedirectResponse
    {
        $this->authorize('manage-categories');

        try {
            DB::beginTransaction();

            $newCategory = $category->replicate();
            $newCategory->name = $category->name . ' (Cópia)';
            $newCategory->slug = $this->generateUniqueSlug($newCategory->name);
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

        $query = Category::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
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
        return response()->json([]);
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

    // Helper methods for advanced statistics removed in minimal schema
}
