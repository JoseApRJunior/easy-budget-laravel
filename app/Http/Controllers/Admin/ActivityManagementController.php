<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ActivitiesExport;
use App\Http\Controllers\Abstracts\Controller;
use App\Models\AreaOfActivity;
use App\Models\Category;
use App\Models\Tenant;
use App\Services\Shared\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ActivityManagementController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(Request $request): View
    {
        $this->authorize('manage-activities');

        $search = $request->get('search');
        $category = $request->get('category');
        $type = $request->get('type');
        $status = $request->get('status', 'all');
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $perPage = $request->get('per_page', 25);

        $query = AreaOfActivity::with(['category', 'tenant'])
            ->withCount(['products', 'services']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('category_id', $category);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        $activities = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->appends($request->query());

        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $activityTypes = AreaOfActivity::select('type')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values();

        $statistics = $this->getActivityStatistics();

        return view('admin.activities.index', compact(
            'activities',
            'search',
            'category',
            'type',
            'status',
            'sortBy',
            'sortOrder',
            'categories',
            'activityTypes',
            'statistics'
        ));
    }

    public function create(): View
    {
        $this->authorize('manage-activities');

        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        $tenants = Tenant::where('is_active', true)
            ->orderBy('name')
            ->get();

        $activityTypes = $this->getActivityTypes();

        return view('admin.activities.create', compact(
            'categories',
            'tenants',
            'activityTypes'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-activities');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'tenant_id' => 'nullable|exists:tenants,id',
            'type' => 'required|string|max:50',
            'code' => 'nullable|string|max:50|unique:activities,code',
            'is_active' => 'boolean',
            'price' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'order' => 'integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'tags' => 'nullable|string|max:500',
            'requirements' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $activity = new AreaOfActivity;
            $activity->fill($validated);

            if (empty($validated['code'])) {
                $activity->code = $this->generateUniqueCode($validated['name']);
            }

            $activity->slug = $this->generateUniqueSlug($validated['name']);
            $activity->save();

            $this->cacheService->forgetPattern('activities.*');

            DB::commit();

            Log::info('Activity created', [
                'activity_id' => $activity->id,
                'name' => $activity->name,
                'category_id' => $activity->category_id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.activities.index')
                ->with('success', 'Atividade criada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating activity', [
                'error' => $e->getMessage(),
                'data' => $validated,
                'admin_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Erro ao criar atividade. Por favor, tente novamente.');
        }
    }

    public function show(AreaOfActivity $activity): View
    {
        $this->authorize('manage-activities');

        $activity->load([
            'category',
            'tenant',
            'products',
            'services',
            'products.tenant',
            'services.tenant',
        ]);

        $statistics = $this->getActivityDetailedStatistics($activity);
        $recentRecords = $this->getRecentActivityRecords($activity);

        return view('admin.activities.show', compact(
            'activity',
            'statistics',
            'recentRecords'
        ));
    }

    public function edit(AreaOfActivity $activity): View
    {
        $this->authorize('manage-activities');

        $activity->load(['category', 'tenant']);

        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        $tenants = Tenant::where('is_active', true)
            ->orderBy('name')
            ->get();

        $activityTypes = $this->getActivityTypes();

        return view('admin.activities.edit', compact(
            'activity',
            'categories',
            'tenants',
            'activityTypes'
        ));
    }

    public function update(Request $request, AreaOfActivity $activity): RedirectResponse
    {
        $this->authorize('manage-activities');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'tenant_id' => 'nullable|exists:tenants,id',
            'type' => 'required|string|max:50',
            'code' => 'nullable|string|max:50|unique:activities,code,'.$activity->id,
            'is_active' => 'boolean',
            'price' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'order' => 'integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'tags' => 'nullable|string|max:500',
            'requirements' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            if ($activity->name !== $validated['name']) {
                $activity->slug = $this->generateUniqueSlug($validated['name'], $activity->id);
            }

            $activity->fill($validated);
            $activity->save();

            $this->cacheService->forgetPattern('activities.*');

            DB::commit();

            Log::info('Activity updated', [
                'activity_id' => $activity->id,
                'name' => $activity->name,
                'category_id' => $activity->category_id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.activities.show', $activity)
                ->with('success', 'Atividade atualizada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating activity', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage(),
                'data' => $validated,
                'admin_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Erro ao atualizar atividade. Por favor, tente novamente.');
        }
    }

    public function destroy(AreaOfActivity $activity): JsonResponse
    {
        $this->authorize('manage-activities');

        if ($activity->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir uma atividade que possui produtos associados.',
            ], 422);
        }

        if ($activity->services()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir uma atividade que possui serviços associados.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $activityName = $activity->name;
            $activity->delete();

            $this->cacheService->forgetPattern('activities.*');

            DB::commit();

            Log::info('Activity deleted', [
                'activity_name' => $activityName,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Atividade excluída com sucesso!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting activity', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir atividade. Por favor, tente novamente.',
            ], 500);
        }
    }

    public function toggleStatus(AreaOfActivity $activity): JsonResponse
    {
        $this->authorize('manage-activities');

        try {
            $activity->is_active = ! $activity->is_active;
            $activity->save();

            $this->cacheService->forgetPattern('activities.*');

            Log::info('Activity status toggled', [
                'activity_id' => $activity->id,
                'name' => $activity->name,
                'new_status' => $activity->is_active ? 'active' : 'inactive',
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status alterado com sucesso!',
                'is_active' => $activity->is_active,
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling activity status', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status. Por favor, tente novamente.',
            ], 500);
        }
    }

    public function duplicate(AreaOfActivity $activity): RedirectResponse
    {
        $this->authorize('manage-activities');

        try {
            DB::beginTransaction();

            $newActivity = $activity->replicate();
            $newActivity->name = $activity->name.' (Cópia)';
            $newActivity->code = $this->generateUniqueCode($newActivity->name);
            $newActivity->slug = $this->generateUniqueSlug($newActivity->name);
            $newActivity->is_active = false;
            $newActivity->save();

            $this->cacheService->forgetPattern('activities.*');

            DB::commit();

            Log::info('Activity duplicated', [
                'original_activity_id' => $activity->id,
                'new_activity_id' => $newActivity->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.activities.edit', $newActivity)
                ->with('success', 'Atividade duplicada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error duplicating activity', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return back()->with('error', 'Erro ao duplicar atividade. Por favor, tente novamente.');
        }
    }

    public function export(Request $request)
    {
        $this->authorize('manage-activities');

        $format = $request->get('format', 'xlsx');
        $search = $request->get('search');
        $category = $request->get('category');
        $type = $request->get('type');
        $status = $request->get('status');

        $query = AreaOfActivity::with(['category', 'tenant'])
            ->withCount(['products', 'services']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('category_id', $category);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status && $status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        $activities = $query->orderBy('name')->get();

        Log::info('Activities exported', [
            'format' => $format,
            'count' => $activities->count(),
            'admin_id' => auth()->id(),
        ]);

        return Excel::download(
            new ActivitiesExport($activities),
            'atividades_'.now()->format('Y-m-d_H-i-s').'.'.$format
        );
    }

    public function getActivitiesByCategory(Request $request): JsonResponse
    {
        $this->authorize('manage-activities');

        $categoryId = $request->get('category_id');
        $excludeId = $request->get('exclude_id');

        $query = AreaOfActivity::where('category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $activities = $query->get(['id', 'name', 'code', 'price']);

        return response()->json($activities);
    }

    public function getActivityPrice(Request $request): JsonResponse
    {
        $this->authorize('manage-activities');

        $activityId = $request->get('activity_id');

        $activity = AreaOfActivity::find($activityId, ['id', 'name', 'price', 'cost', 'duration', 'unit']);

        if (! $activity) {
            return response()->json([
                'success' => false,
                'message' => 'Atividade não encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $activity,
        ]);
    }

    protected function generateUniqueCode(string $name, ?int $excludeId = null): string
    {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6));

        if (empty($code)) {
            $code = 'ATV';
        }

        $baseCode = $code;
        $counter = 1;

        while (true) {
            $query = AreaOfActivity::where('code', $code);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (! $query->exists()) {
                break;
            }

            $code = $baseCode.str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $code;
    }

    protected function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $baseSlug = $slug;
        $counter = 1;

        while (true) {
            $query = AreaOfActivity::where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (! $query->exists()) {
                break;
            }

            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function getActivityStatistics(): array
    {
        return Cache::remember('admin.activities.statistics', 300, function () {
            return [
                'total' => AreaOfActivity::count(),
                'active' => AreaOfActivity::where('is_active', true)->count(),
                'inactive' => AreaOfActivity::where('is_active', false)->count(),
                'with_products' => AreaOfActivity::has('products')->count(),
                'with_services' => AreaOfActivity::has('services')->count(),
                'by_type' => AreaOfActivity::select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'avg_price' => AreaOfActivity::where('price', '>', 0)->avg('price') ?? 0,
                'avg_cost' => AreaOfActivity::where('cost', '>', 0)->avg('cost') ?? 0,
            ];
        });
    }

    protected function getActivityDetailedStatistics(AreaOfActivity $activity): array
    {
        return [
            'total_products' => $activity->products()->count(),
            'active_products' => $activity->products()->where('is_active', true)->count(),
            'total_services' => $activity->services()->count(),
            'active_services' => $activity->services()->where('is_active', true)->count(),
            'total_revenue' => $activity->products()->sum('price') + $activity->services()->sum('price'),
            'total_cost' => $activity->products()->sum('cost') + $activity->services()->sum('cost'),
            'usage_by_tenant' => $activity->products()
                ->select('tenant_id', DB::raw('count(*) as count'))
                ->groupBy('tenant_id')
                ->union(
                    $activity->services()
                        ->select('tenant_id', DB::raw('count(*) as count'))
                        ->groupBy('tenant_id')
                )
                ->with('tenant:id,name')
                ->get(),
            'recent_records' => $this->getRecentActivityRecords($activity),
        ];
    }

    protected function getRecentActivityRecords(AreaOfActivity $activity)
    {
        $products = $activity->products()
            ->with(['tenant', 'category'])
            ->latest()
            ->limit(5)
            ->get();

        $services = $activity->services()
            ->with(['tenant', 'category'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'products' => $products,
            'services' => $services,
        ];
    }

    protected function getActivityTypes(): array
    {
        return [
            'production' => 'Produção',
            'service' => 'Serviço',
            'consulting' => 'Consultoria',
            'training' => 'Treinamento',
            'maintenance' => 'Manutenção',
            'development' => 'Desenvolvimento',
            'marketing' => 'Marketing',
            'sales' => 'Vendas',
            'support' => 'Suporte',
            'other' => 'Outro',
        ];
    }
}
