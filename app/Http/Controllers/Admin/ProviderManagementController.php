<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\Provider\ProviderDTO;
use App\Exports\ProvidersExport;
use App\Http\Controllers\Abstracts\Controller;
use App\Models\Address;
use App\Models\CommonData;
use App\Models\Provider;
use App\Models\User;
use App\Services\Application\Admin\AdminProviderService;
use App\Services\Shared\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ProviderManagementController extends Controller
{
    protected CacheService $cacheService;

    protected AdminProviderService $adminProviderService;

    public function __construct(CacheService $cacheService, AdminProviderService $adminProviderService)
    {
        $this->cacheService = $cacheService;
        $this->adminProviderService = $adminProviderService;
    }

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('manage-providers');

        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status', 'all'),
            'tenant_id' => $request->get('tenant'),
            'plan_id' => $request->get('plan'),
            'sort_by' => $request->get('sort_by', 'name'),
            'sort_order' => $request->get('sort_order', 'asc'),
        ];

        $perPage = $request->get('per_page', 25);

        $result = $this->adminProviderService->getProvidersPaginated($filters, $perPage);

        if (! $result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $data = $result->getData();

        return view('admin.providers.index', [
            'providers' => $data['providers']->appends($request->query()),
            'search' => $filters['search'],
            'status' => $filters['status'],
            'tenant' => $filters['tenant_id'],
            'plan' => $filters['plan_id'],
            'sortBy' => $filters['sort_by'],
            'sortOrder' => $filters['sort_order'],
            'tenants' => $data['tenants'],
            'statistics' => $data['statistics'],
        ]);
    }

    public function create(): View
    {
        $this->authorize('manage-providers');

        return view('admin.providers.create', $this->adminProviderService->getFormData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-providers');

        $validated = $request->validate(array_merge(
            Provider::businessRules(),
            User::businessRules(),
            CommonData::businessRules(),
            Address::businessRules()
        ));

        $dto = ProviderDTO::fromRequest($validated);
        $result = $this->adminProviderService->storeProvider($dto);

        if (! $result->isSuccess()) {
            return redirect()->back()->withInput()->with('error', $result->getMessage());
        }

        return redirect()->route('admin.providers.index')->with('success', $result->getMessage());
    }

    public function show(Provider $provider): View|RedirectResponse
    {
        $this->authorize('manage-providers');

        $result = $this->adminProviderService->getProviderDetails($provider->id);

        if (! $result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $data = $result->getData();

        return view('admin.providers.show', [
            'provider' => $data['provider'],
            'statistics' => $data['statistics'],
            'recentInteractions' => $data['recentInteractions'],
        ]);
    }

    public function edit(Provider $provider): View
    {
        $this->authorize('manage-providers');

        $result = $this->adminProviderService->getProviderDetails($provider->id);

        if (! $result->isSuccess()) {
            return redirect()->route('admin.providers.index')->with('error', $result->getMessage());
        }

        return view('admin.providers.edit', $result->getData());
    }

    public function update(Request $request, Provider $provider): RedirectResponse
    {
        $this->authorize('manage-providers');

        $validated = $request->validate(array_merge(
            Provider::businessRules(),
            User::businessRules(),
            CommonData::businessRules(),
            Address::businessRules()
        ));

        $dto = ProviderDTO::fromRequest($validated);
        $result = $this->adminProviderService->updateProvider($provider->id, $dto);

        if (! $result->isSuccess()) {
            return redirect()->back()->withInput()->with('error', $result->getMessage());
        }

        return redirect()->route('admin.providers.index')->with('success', $result->getMessage());
    }

    public function destroy(Provider $provider): JsonResponse
    {
        $this->authorize('manage-providers');

        $result = $this->adminProviderService->deleteProvider($provider->id);

        return response()->json([
            'success' => $result->isSuccess(),
            'message' => $result->getMessage(),
        ], $result->isSuccess() ? 200 : ($result->getCode() === 422 ? 422 : 500));
    }

    public function toggleStatus(Provider $provider): JsonResponse
    {
        $this->authorize('manage-providers');

        $result = $this->adminProviderService->toggleStatus($provider->id);

        return response()->json([
            'success' => $result->isSuccess(),
            'message' => $result->getMessage(),
            'is_active' => $result->isSuccess() ? $result->getData()->is_active : null,
        ], $result->isSuccess() ? 200 : 500);
    }

    public function export(Request $request)
    {
        $this->authorize('manage-providers');

        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status', 'all'),
            'tenant_id' => $request->get('tenant'),
            'plan_id' => $request->get('plan'),
        ];

        $providers = $this->adminProviderService->getExportData($filters);

        return Excel::download(new ProvidersExport($providers), 'providers_'.date('Y-m-d_H-i-s').'.xlsx');
    }

    public function getProvidersByTenant(Request $request): JsonResponse
    {
        $this->authorize('manage-providers');

        $tenantId = (int) $request->get('tenant_id');
        $search = $request->get('search');

        $providers = $this->adminProviderService->getProvidersByTenant($tenantId, $search);

        return response()->json($providers);
    }
}
