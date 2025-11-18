<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Provider;
use App\Models\Tenant;
use App\Services\Shared\CacheService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProvidersExport;

class ProviderManagementController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(Request $request): View
    {
        $this->authorize('manage-providers');

        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $tenant = $request->get('tenant');
        $plan = $request->get('plan');
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $perPage = $request->get('per_page', 25);

        $query = Provider::with(['tenant', 'plan', 'city', 'state'])
            ->withCount(['customers', 'budgets', 'services', 'invoices']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        if ($tenant) {
            $query->where('tenant_id', $tenant);
        }

        if ($plan) {
            $query->where('plan_id', $plan);
        }

        $providers = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->appends($request->query());

        $tenants = Tenant::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $statistics = $this->getProviderStatistics();

        return view('admin.providers.index', compact(
            'providers',
            'search',
            'status',
            'tenant',
            'plan',
            'sortBy',
            'sortOrder',
            'tenants',
            'statistics'
        ));
    }

    public function create(): View
    {
        $this->authorize('manage-providers');

        $tenants = Tenant::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.providers.create', compact('tenants'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-providers');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:providers,email',
            'phone' => 'nullable|string|max:20',
            'document' => 'nullable|string|max:20|unique:providers,document',
            'company_name' => 'nullable|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'state_registration' => 'nullable|string|max:50',
            'municipal_registration' => 'nullable|string|max:50',
            'tenant_id' => 'nullable|exists:tenants,id',
            'zip_code' => 'nullable|string|max:10',
            'address' => 'nullable|string|max:255',
            'number' => 'nullable|string|max:20',
            'complement' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $provider = new Provider();
            $provider->fill($validated);
            $provider->save();

            $this->cacheService->forgetPattern('providers.*');

            DB::commit();

            Log::info('Provider created', [
                'provider_id' => $provider->id,
                'name' => $provider->name,
                'email' => $provider->email,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.providers.index')
                ->with('success', 'Fornecedor criado com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating provider', [
                'error' => $e->getMessage(),
                'data' => $validated,
                'admin_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Erro ao criar fornecedor. Por favor, tente novamente.');
        }
    }

    public function show(Provider $provider): View
    {
        $this->authorize('manage-providers');

        $provider->load([
            'tenant',
            'plan',
            'city',
            'state',
            'customers',
            'budgets',
            'services',
            'invoices'
        ]);

        $statistics = $this->getProviderDetailedStatistics($provider);
        $recentInteractions = $this->getRecentProviderInteractions($provider);

        return view('admin.providers.show', compact(
            'provider',
            'statistics',
            'recentInteractions'
        ));
    }

    public function edit(Provider $provider): View
    {
        $this->authorize('manage-providers');

        $provider->load(['tenant', 'plan', 'city', 'state']);

        $tenants = Tenant::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.providers.edit', compact('provider', 'tenants'));
    }

    public function update(Request $request, Provider $provider): RedirectResponse
    {
        $this->authorize('manage-providers');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:providers,email,' . $provider->id,
            'phone' => 'nullable|string|max:20',
            'document' => 'nullable|string|max:20|unique:providers,document,' . $provider->id,
            'company_name' => 'nullable|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'state_registration' => 'nullable|string|max:50',
            'municipal_registration' => 'nullable|string|max:50',
            'tenant_id' => 'nullable|exists:tenants,id',
            'zip_code' => 'nullable|string|max:10',
            'address' => 'nullable|string|max:255',
            'number' => 'nullable|string|max:20',
            'complement' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $provider->fill($validated);
            $provider->save();

            $this->cacheService->forgetPattern('providers.*');

            DB::commit();

            Log::info('Provider updated', [
                'provider_id' => $provider->id,
                'name' => $provider->name,
                'email' => $provider->email,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.providers.show', $provider)
                ->with('success', 'Fornecedor atualizado com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating provider', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
                'data' => $validated,
                'admin_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Erro ao atualizar fornecedor. Por favor, tente novamente.');
        }
    }

    public function destroy(Provider $provider): JsonResponse
    {
        $this->authorize('manage-providers');

        if ($provider->customers()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir um fornecedor que possui clientes associados.'
            ], 422);
        }

        if ($provider->budgets()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir um fornecedor que possui orçamentos associados.'
            ], 422);
        }

        if ($provider->services()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir um fornecedor que possui serviços associados.'
            ], 422);
        }

        if ($provider->invoices()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir um fornecedor que possui faturas associadas.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $providerName = $provider->name;
            $provider->delete();

            $this->cacheService->forgetPattern('providers.*');

            DB::commit();

            Log::info('Provider deleted', [
                'provider_name' => $providerName,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fornecedor excluído com sucesso!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting provider', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir fornecedor. Por favor, tente novamente.'
            ], 500);
        }
    }

    public function toggleStatus(Provider $provider): JsonResponse
    {
        $this->authorize('manage-providers');

        try {
            $provider->is_active = !$provider->is_active;
            $provider->save();

            $this->cacheService->forgetPattern('providers.*');

            Log::info('Provider status toggled', [
                'provider_id' => $provider->id,
                'name' => $provider->name,
                'new_status' => $provider->is_active ? 'active' : 'inactive',
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status alterado com sucesso!',
                'is_active' => $provider->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling provider status', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status. Por favor, tente novamente.'
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $this->authorize('manage-providers');

        $format = $request->get('format', 'xlsx');
        $search = $request->get('search');
        $status = $request->get('status');
        $tenant = $request->get('tenant');
        $plan = $request->get('plan');

        $query = Provider::with(['tenant', 'plan', 'city', 'state'])
            ->withCount(['customers', 'budgets', 'services', 'invoices']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($status && $status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        if ($tenant) {
            $query->where('tenant_id', $tenant);
        }

        if ($plan) {
            $query->where('plan_id', $plan);
        }

        $providers = $query->orderBy('name')->get();

        Log::info('Providers exported', [
            'format' => $format,
            'count' => $providers->count(),
            'admin_id' => auth()->id(),
        ]);

        return Excel::download(
            new ProvidersExport($providers),
            'fornecedores_' . now()->format('Y-m-d_H-i-s') . '.' . $format
        );
    }

    public function getProvidersByTenant(Request $request): JsonResponse
    {
        $this->authorize('manage-providers');

        $tenantId = $request->get('tenant_id');
        $search = $request->get('search');

        $query = Provider::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $providers = $query->get(['id', 'name', 'email', 'phone', 'document']);

        return response()->json($providers);
    }

    protected function getProviderStatistics(): array
    {
        return Cache::remember('admin.providers.statistics', 300, function () {
            return [
                'total' => Provider::count(),
                'active' => Provider::where('is_active', true)->count(),
                'inactive' => Provider::where('is_active', false)->count(),
                'with_customers' => Provider::has('customers')->count(),
                'with_budgets' => Provider::has('budgets')->count(),
                'with_services' => Provider::has('services')->count(),
                'with_invoices' => Provider::has('invoices')->count(),
                'by_tenant' => Provider::select('tenant_id', DB::raw('count(*) as count'))
                    ->groupBy('tenant_id')
                    ->with('tenant:id,name')
                    ->get(),
                'by_plan' => Provider::select('plan_id', DB::raw('count(*) as count'))
                    ->groupBy('plan_id')
                    ->with('plan:id,name')
                    ->get(),
            ];
        });
    }

    protected function getProviderDetailedStatistics(Provider $provider): array
    {
        return [
            'total_customers' => $provider->customers()->count(),
            'active_customers' => $provider->customers()->where('is_active', true)->count(),
            'total_budgets' => $provider->budgets()->count(),
            'active_budgets' => $provider->budgets()->where('status', 'active')->count(),
            'total_services' => $provider->services()->count(),
            'active_services' => $provider->services()->where('status', 'active')->count(),
            'total_invoices' => $provider->invoices()->count(),
            'paid_invoices' => $provider->invoices()->where('status', 'paid')->count(),
            'total_revenue' => $provider->invoices()->where('status', 'paid')->sum('total_value'),
            'avg_budget_value' => $provider->budgets()->avg('total_value') ?? 0,
            'avg_invoice_value' => $provider->invoices()->where('status', 'paid')->avg('total_value') ?? 0,
            'recent_customers' => $provider->customers()->latest()->limit(5)->get(),
            'recent_budgets' => $provider->budgets()->latest()->limit(5)->get(),
            'recent_services' => $provider->services()->latest()->limit(5)->get(),
            'recent_invoices' => $provider->invoices()->latest()->limit(5)->get(),
        ];
    }

    protected function getRecentProviderInteractions(Provider $provider)
    {
        $customers = $provider->customers()->latest()->limit(3)->get();
        $budgets = $provider->budgets()->latest()->limit(3)->get();
        $services = $provider->services()->latest()->limit(3)->get();
        $invoices = $provider->invoices()->latest()->limit(3)->get();

        return [
            'customers' => $customers,
            'budgets' => $budgets,
            'services' => $services,
            'invoices' => $invoices,
        ];
    }
}