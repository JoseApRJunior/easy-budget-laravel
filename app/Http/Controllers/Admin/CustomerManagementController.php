<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\CustomersExport;
use App\Http\Controllers\Abstracts\Controller;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\Shared\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class CustomerManagementController extends Controller
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('manage-customers');

        $search = $request->get('search');
        $type = $request->get('type');
        $status = $request->get('status', 'all');
        $tenant = $request->get('tenant');
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $perPage = $request->get('per_page', 25);

        $query = Customer::with(['tenant', 'address'])
            ->withCount(['budgets', 'services', 'invoices']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        if ($tenant) {
            $query->where('tenant_id', $tenant);
        }

        $customers = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->appends($request->query());

        $tenants = Tenant::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $statistics = $this->getCustomerStatistics();

        return view('admin.customers.index', compact(
            'customers',
            'search',
            'type',
            'status',
            'tenant',
            'sortBy',
            'sortOrder',
            'tenants',
            'statistics'
        ));
    }

    public function create(): View
    {
        $this->authorize('manage-customers');

        $tenants = Tenant::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.customers.create', compact('tenants'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-customers');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'document' => 'nullable|string|max:20|unique:customers,document',
            'type' => 'required|in:individual,company',
            'company_name' => 'nullable|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'state_registration' => 'nullable|string|max:50',
            'municipal_registration' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
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

        DB::beginTransaction();

        $customer = new Customer;
        $customer->fill($validated);
        $customer->save();

        $this->cacheService->forgetPattern('customers.*');

        DB::commit();

        Log::info('Customer created', [
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'admin_id' => auth()->id(),
        ]);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Cliente criado com sucesso!');
    }

    public function show(Customer $customer): View
    {
        $this->authorize('manage-customers');

        $customer->load([
            'tenant',
            'address',
            'budgets',
            'services',
            'invoices',
        ]);

        $statistics = $this->getCustomerDetailedStatistics($customer);
        $recentInteractions = $this->getRecentCustomerInteractions($customer);

        return view('admin.customers.show', compact(
            'customer',
            'statistics',
            'recentInteractions'
        ));
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('manage-customers');

        $customer->load(['tenant', 'address']);

        $tenants = Tenant::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.customers.edit', compact('customer', 'tenants'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('manage-customers');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email,'.$customer->id,
            'phone' => 'nullable|string|max:20',
            'document' => 'nullable|string|max:20|unique:customers,document,'.$customer->id,
            'type' => 'required|in:individual,company',
            'company_name' => 'nullable|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'state_registration' => 'nullable|string|max:50',
            'municipal_registration' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
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

        DB::beginTransaction();

        $customer->fill($validated);
        $customer->save();

        $this->cacheService->forgetPattern('customers.*');

        DB::commit();

        Log::info('Customer updated', [
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'admin_id' => auth()->id(),
        ]);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('manage-customers');

        if ($customer->budgets()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir um cliente que possui orçamentos associados.',
            ], 422);
        }

        if ($customer->services()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir um cliente que possui serviços associados.',
            ], 422);
        }

        if ($customer->invoices()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir um cliente que possui faturas associadas.',
            ], 422);
        }

        DB::beginTransaction();

        $customerName = $customer->name;
        $customer->delete();

        $this->cacheService->forgetPattern('customers.*');

        DB::commit();

        Log::info('Customer deleted', [
            'customer_name' => $customerName,
            'admin_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cliente excluído com sucesso!',
        ]);
    }

    public function toggleStatus(Customer $customer): JsonResponse
    {
        $this->authorize('manage-customers');

        $customer->is_active = ! $customer->is_active;
        $customer->save();

        $this->cacheService->forgetPattern('customers.*');

        Log::info('Customer status toggled', [
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'new_status' => $customer->is_active ? 'active' : 'inactive',
            'admin_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status alterado com sucesso!',
            'is_active' => $customer->is_active,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('manage-customers');

        $format = $request->get('format', 'xlsx');
        $search = $request->get('search');
        $type = $request->get('type');
        $status = $request->get('status');
        $tenant = $request->get('tenant');

        $query = Customer::with(['tenant', 'address'])
            ->withCount(['budgets', 'services', 'invoices']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status && $status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        if ($tenant) {
            $query->where('tenant_id', $tenant);
        }

        $customers = $query->orderBy('name')->get();

        Log::info('Customers exported', [
            'format' => $format,
            'count' => $customers->count(),
            'admin_id' => auth()->id(),
        ]);

        return Excel::download(
            new CustomersExport($customers),
            'clientes_'.now()->format('Y-m-d_H-i-s').'.'.$format
        );
    }

    public function getCustomersByTenant(Request $request): JsonResponse
    {
        $this->authorize('manage-customers');

        $tenantId = $request->get('tenant_id');
        $search = $request->get('search');

        $query = Customer::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->get(['id', 'name', 'email', 'phone', 'document']);

        return response()->json($customers);
    }

    protected function getCustomerStatistics(): array
    {
        return Cache::remember('admin.customers.statistics', 300, function () {
            return [
                'total' => Customer::count(),
                'active' => Customer::where('is_active', true)->count(),
                'inactive' => Customer::where('is_active', false)->count(),
                'individual' => Customer::where('type', 'individual')->count(),
                'company' => Customer::where('type', 'company')->count(),
                'with_budgets' => Customer::has('budgets')->count(),
                'with_services' => Customer::has('services')->count(),
                'with_invoices' => Customer::has('invoices')->count(),
                'by_tenant' => Customer::select('tenant_id', DB::raw('count(*) as count'))
                    ->groupBy('tenant_id')
                    ->with('tenant:id,name')
                    ->get(),
            ];
        });
    }

    protected function getCustomerDetailedStatistics(Customer $customer): array
    {
        return [
            'total_budgets' => $customer->budgets()->count(),
            'active_budgets' => $customer->budgets()->where('status', 'active')->count(),
            'total_services' => $customer->services()->count(),
            'active_services' => $customer->services()->where('status', 'active')->count(),
            'total_invoices' => $customer->invoices()->count(),
            'paid_invoices' => $customer->invoices()->where('status', 'paid')->count(),
            'total_revenue' => $customer->invoices()->where('status', 'paid')->sum('total_value'),
            'avg_budget_value' => $customer->budgets()->avg('total_value') ?? 0,
            'avg_invoice_value' => $customer->invoices()->where('status', 'paid')->avg('total_value') ?? 0,
            'recent_budgets' => $customer->budgets()->latest()->limit(5)->get(),
            'recent_services' => $customer->services()->latest()->limit(5)->get(),
            'recent_invoices' => $customer->invoices()->latest()->limit(5)->get(),
        ];
    }

    protected function getRecentCustomerInteractions(Customer $customer)
    {
        $budgets = $customer->budgets()->latest()->limit(3)->get();
        $services = $customer->services()->latest()->limit(3)->get();
        $invoices = $customer->invoices()->latest()->limit(3)->get();

        return [
            'budgets' => $budgets,
            'services' => $services,
            'invoices' => $invoices,
        ];
    }
}
