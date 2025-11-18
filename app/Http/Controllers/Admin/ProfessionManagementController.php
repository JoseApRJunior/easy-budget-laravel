<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Profession;
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
use App\Exports\ProfessionsExport;

class ProfessionManagementController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(Request $request): View
    {
        $this->authorize('manage-professions');

        $search = $request->get('search');
        $type = $request->get('type');
        $status = $request->get('status', 'all');
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $perPage = $request->get('per_page', 25);

        $query = Profession::with(['tenant'])
            ->withCount(['users', 'providers']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        $professions = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->appends($request->query());

        $professionTypes = Profession::select('type')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values();

        $statistics = $this->getProfessionStatistics();

        return view('admin.professions.index', compact(
            'professions',
            'search',
            'type',
            'status',
            'sortBy',
            'sortOrder',
            'professionTypes',
            'statistics'
        ));
    }

    public function create(): View
    {
        $this->authorize('manage-professions');

        $tenants = Tenant::where('is_active', true)
            ->orderBy('name')
            ->get();

        $professionTypes = $this->getProfessionTypes();

        return view('admin.professions.create', compact(
            'tenants',
            'professionTypes'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-professions');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|max:50',
            'tenant_id' => 'nullable|exists:tenants,id',
            'code' => 'nullable|string|max:50|unique:professions,code',
            'is_active' => 'boolean',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'order' => 'integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'requirements' => 'nullable|string|max:1000',
            'certifications' => 'nullable|string|max:1000',
            'skills' => 'nullable|string|max:1000',
            'average_salary' => 'nullable|numeric|min:0',
            'job_market' => 'nullable|string|max:50',
            'education_level' => 'nullable|string|max:50',
        ]);

        try {
            DB::beginTransaction();

            $profession = new Profession();
            $profession->fill($validated);
            
            if (empty($validated['code'])) {
                $profession->code = $this->generateUniqueCode($validated['name']);
            }
            
            $profession->slug = $this->generateUniqueSlug($validated['name']);
            $profession->save();

            $this->cacheService->forgetPattern('professions.*');

            DB::commit();

            Log::info('Profession created', [
                'profession_id' => $profession->id,
                'name' => $profession->name,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.professions.index')
                ->with('success', 'Profissão criada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating profession', [
                'error' => $e->getMessage(),
                'data' => $validated,
                'admin_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Erro ao criar profissão. Por favor, tente novamente.');
        }
    }

    public function show(Profession $profession): View
    {
        $this->authorize('manage-professions');

        $profession->load([
            'tenant',
            'users',
            'providers',
            'users.tenant',
            'providers.tenant'
        ]);

        $statistics = $this->getProfessionDetailedStatistics($profession);
        $recentRecords = $this->getRecentProfessionRecords($profession);

        return view('admin.professions.show', compact(
            'profession',
            'statistics',
            'recentRecords'
        ));
    }

    public function edit(Profession $profession): View
    {
        $this->authorize('manage-professions');

        $profession->load(['tenant']);

        $tenants = Tenant::where('is_active', true)
            ->orderBy('name')
            ->get();

        $professionTypes = $this->getProfessionTypes();

        return view('admin.professions.edit', compact(
            'profession',
            'tenants',
            'professionTypes'
        ));
    }

    public function update(Request $request, Profession $profession): RedirectResponse
    {
        $this->authorize('manage-professions');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|max:50',
            'tenant_id' => 'nullable|exists:tenants,id',
            'code' => 'nullable|string|max:50|unique:professions,code,' . $profession->id,
            'is_active' => 'boolean',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'order' => 'integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'requirements' => 'nullable|string|max:1000',
            'certifications' => 'nullable|string|max:1000',
            'skills' => 'nullable|string|max:1000',
            'average_salary' => 'nullable|numeric|min:0',
            'job_market' => 'nullable|string|max:50',
            'education_level' => 'nullable|string|max:50',
        ]);

        try {
            DB::beginTransaction();

            if ($profession->name !== $validated['name']) {
                $profession->slug = $this->generateUniqueSlug($validated['name'], $profession->id);
            }

            $profession->fill($validated);
            $profession->save();

            $this->cacheService->forgetPattern('professions.*');

            DB::commit();

            Log::info('Profession updated', [
                'profession_id' => $profession->id,
                'name' => $profession->name,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.professions.show', $profession)
                ->with('success', 'Profissão atualizada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating profession', [
                'profession_id' => $profession->id,
                'error' => $e->getMessage(),
                'data' => $validated,
                'admin_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Erro ao atualizar profissão. Por favor, tente novamente.');
        }
    }

    public function destroy(Profession $profession): JsonResponse
    {
        $this->authorize('manage-professions');

        if ($profession->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir uma profissão que possui usuários associados.'
            ], 422);
        }

        if ($profession->providers()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir uma profissão que possui fornecedores associados.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $professionName = $profession->name;
            $profession->delete();

            $this->cacheService->forgetPattern('professions.*');

            DB::commit();

            Log::info('Profession deleted', [
                'profession_name' => $professionName,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profissão excluída com sucesso!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting profession', [
                'profession_id' => $profession->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir profissão. Por favor, tente novamente.'
            ], 500);
        }
    }

    public function toggleStatus(Profession $profession): JsonResponse
    {
        $this->authorize('manage-professions');

        try {
            $profession->is_active = !$profession->is_active;
            $profession->save();

            $this->cacheService->forgetPattern('professions.*');

            Log::info('Profession status toggled', [
                'profession_id' => $profession->id,
                'name' => $profession->name,
                'new_status' => $profession->is_active ? 'active' : 'inactive',
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status alterado com sucesso!',
                'is_active' => $profession->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling profession status', [
                'profession_id' => $profession->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status. Por favor, tente novamente.'
            ], 500);
        }
    }

    public function duplicate(Profession $profession): RedirectResponse
    {
        $this->authorize('manage-professions');

        try {
            DB::beginTransaction();

            $newProfession = $profession->replicate();
            $newProfession->name = $profession->name . ' (Cópia)';
            $newProfession->code = $this->generateUniqueCode($newProfession->name);
            $newProfession->slug = $this->generateUniqueSlug($newProfession->name);
            $newProfession->is_active = false;
            $newProfession->save();

            $this->cacheService->forgetPattern('professions.*');

            DB::commit();

            Log::info('Profession duplicated', [
                'original_profession_id' => $profession->id,
                'new_profession_id' => $newProfession->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.professions.edit', $newProfession)
                ->with('success', 'Profissão duplicada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error duplicating profession', [
                'profession_id' => $profession->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return back()->with('error', 'Erro ao duplicar profissão. Por favor, tente novamente.');
        }
    }

    public function export(Request $request)
    {
        $this->authorize('manage-professions');

        $format = $request->get('format', 'xlsx');
        $search = $request->get('search');
        $type = $request->get('type');
        $status = $request->get('status');

        $query = Profession::with(['tenant'])
            ->withCount(['users', 'providers']);

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

        $professions = $query->orderBy('name')->get();

        Log::info('Professions exported', [
            'format' => $format,
            'count' => $professions->count(),
            'admin_id' => auth()->id(),
        ]);

        return Excel::download(
            new ProfessionsExport($professions),
            'profissoes_' . now()->format('Y-m-d_H-i-s') . '.' . $format
        );
    }

    public function getProfessionsByType(Request $request): JsonResponse
    {
        $this->authorize('manage-professions');

        $type = $request->get('type');
        $excludeId = $request->get('exclude_id');

        $query = Profession::where('type', $type)
            ->where('is_active', true)
            ->orderBy('name');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $professions = $query->get(['id', 'name', 'code', 'average_salary']);

        return response()->json($professions);
    }

    protected function generateUniqueCode(string $name, ?int $excludeId = null): string
    {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6));
        
        if (empty($code)) {
            $code = 'PROF';
        }

        $baseCode = $code;
        $counter = 1;

        while (true) {
            $query = Profession::where('code', $code);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $code = $baseCode . str_pad($counter, 2, '0', STR_PAD_LEFT);
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
            $query = Profession::where('slug', $slug);
            
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

    protected function getProfessionStatistics(): array
    {
        return Cache::remember('admin.professions.statistics', 300, function () {
            return [
                'total' => Profession::count(),
                'active' => Profession::where('is_active', true)->count(),
                'inactive' => Profession::where('is_active', false)->count(),
                'with_users' => Profession::has('users')->count(),
                'with_providers' => Profession::has('providers')->count(),
                'by_type' => Profession::select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'avg_salary' => Profession::where('average_salary', '>', 0)->avg('average_salary') ?? 0,
                'by_education' => Profession::select('education_level', DB::raw('count(*) as count'))
                    ->whereNotNull('education_level')
                    ->groupBy('education_level')
                    ->pluck('count', 'education_level')
                    ->toArray(),
            ];
        });
    }

    protected function getProfessionDetailedStatistics(Profession $profession): array
    {
        return [
            'total_users' => $profession->users()->count(),
            'active_users' => $profession->users()->where('is_active', true)->count(),
            'total_providers' => $profession->providers()->count(),
            'active_providers' => $profession->providers()->where('is_active', true)->count(),
            'usage_by_tenant' => $profession->users()
                ->select('tenant_id', DB::raw('count(*) as count'))
                ->groupBy('tenant_id')
                ->union(
                    $profession->providers()
                        ->select('tenant_id', DB::raw('count(*) as count'))
                        ->groupBy('tenant_id')
                )
                ->with('tenant:id,name')
                ->get(),
            'recent_records' => $this->getRecentProfessionRecords($profession),
        ];
    }

    protected function getRecentProfessionRecords(Profession $profession)
    {
        $users = $profession->users()
            ->with(['tenant', 'profession'])
            ->latest()
            ->limit(5)
            ->get();

        $providers = $profession->providers()
            ->with(['tenant', 'profession'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'users' => $users,
            'providers' => $providers,
        ];
    }

    protected function getProfessionTypes(): array
    {
        return [
            'health' => 'Saúde',
            'technology' => 'Tecnologia',
            'education' => 'Educação',
            'engineering' => 'Engenharia',
            'business' => 'Negócios',
            'arts' => 'Artes',
            'sciences' => 'Ciências',
            'services' => 'Serviços',
            'trades' => 'Ofícios',
            'administration' => 'Administração',
            'legal' => 'Jurídico',
            'finance' => 'Finanças',
            'marketing' => 'Marketing',
            'sales' => 'Vendas',
            'other' => 'Outro',
        ];
    }
}