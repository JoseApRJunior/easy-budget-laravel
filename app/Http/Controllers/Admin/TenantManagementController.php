<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Tenant;
use App\Services\Domain\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TenantManagementController extends Controller
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Display tenants management dashboard
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        $query = Tenant::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $tenants = $query->orderBy($sort, $direction)
            ->paginate(15)
            ->appends($request->query());

        return view('admin.tenants.index', compact('tenants', 'search', 'status', 'sort', 'direction'));
    }

    /**
     * Show the form for creating a new tenant
     */
    public function create(): View
    {
        return view('admin.tenants.create');
    }

    /**
     * Store a newly created tenant
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tenants',
        ]);

        DB::beginTransaction();
        try {
            $tenant = Tenant::create([
                'name' => $validated['name'],
            ]);

            DB::commit();

            return redirect()->route('admin.tenants.show', $tenant)
                ->with('success', 'Tenant criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Erro ao criar tenant: '.$e->getMessage());
        }
    }

    /**
     * Display the specified tenant
     */
    public function show(Tenant $tenant): View
    {
        $tenant->load(['users', 'subscriptions.plan']);

        return view('admin.tenants.show', compact('tenant'));
    }

    /**
     * Show the form for editing the specified tenant
     */
    public function edit(Tenant $tenant): View
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    /**
     * Update the specified tenant
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tenants,name,'.$tenant->id,
        ]);

        DB::beginTransaction();
        try {
            $tenant->name = $validated['name'];
            $tenant->save();

            DB::commit();

            return redirect()->route('admin.tenants.show', $tenant)
                ->with('success', 'Tenant atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Erro ao atualizar tenant: '.$e->getMessage());
        }
    }

    /**
     * Suspend the specified tenant
     */
    public function suspend(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => false]);

        return back()->with('success', 'Tenant suspenso com sucesso!');
    }

    /**
     * Activate the specified tenant
     */
    public function activate(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => true]);

        return back()->with('success', 'Tenant ativado com sucesso!');
    }

    /**
     * Remove the specified tenant
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $tenant->delete();
            DB::commit();

            return redirect()->route('admin.tenants.index')
                ->with('success', 'Tenant excluído com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Erro ao excluir tenant: '.$e->getMessage());
        }
    }

    /**
     * Display tenant financial data
     */
    public function financialData(Tenant $tenant): View
    {
        $financialData = $this->tenantService->getFinancialData($tenant->id);

        return view('admin.tenants.financial-data', compact('tenant', 'financialData'));
    }

    /**
     * Display tenant analytics
     */
    public function analytics(Tenant $tenant): View
    {
        $analytics = $this->tenantService->getAnalytics($tenant->id);

        return view('admin.tenants.analytics', compact('tenant', 'analytics'));
    }

    /**
     * Display tenant billing information
     */
    public function billing(Tenant $tenant): View
    {
        $billingData = $this->tenantService->getBillingData($tenant->id);

        return view('admin.tenants.billing', compact('tenant', 'billingData'));
    }

    /**
     * Impersonate tenant admin
     */
    public function impersonate(Tenant $tenant): RedirectResponse
    {
        $adminUser = $tenant->users()->where('role', 'admin')->first();

        if (! $adminUser) {
            return back()->with('error', 'Tenant não possui usuário administrador.');
        }

        session(['impersonate' => $adminUser->id]);
        session(['original_user' => auth()->id()]);

        return redirect()->route('provider.dashboard')
            ->with('success', 'Você está agora impersonando o administrador do tenant '.$tenant->name);
    }
}
