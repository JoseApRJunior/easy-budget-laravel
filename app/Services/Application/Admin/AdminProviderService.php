<?php

declare(strict_types=1);

namespace App\Services\Application\Admin;

use App\DTOs\Provider\ProviderDTO;
use App\Models\Provider;
use App\Repositories\AddressRepository;
use App\Repositories\AreaOfActivityRepository;
use App\Repositories\BusinessDataRepository;
use App\Repositories\CommonDataRepository;
use App\Repositories\ContactRepository;
use App\Repositories\PlanRepository;
use App\Repositories\ProfessionRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use App\Services\Core\Traits\HasSafeExecution;
use App\Services\Shared\CacheService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminProviderService
{
    use HasSafeExecution;

    public function __construct(
        private ProviderRepository $providerRepository,
        private TenantRepository $tenantRepository,
        private PlanRepository $planRepository,
        private ProfessionRepository $professionRepository,
        private AreaOfActivityRepository $areaOfActivityRepository,
        private UserRepository $userRepository,
        private CommonDataRepository $commonDataRepository,
        private AddressRepository $addressRepository,
        private ContactRepository $contactRepository,
        private BusinessDataRepository $businessDataRepository,
        private CacheService $cacheService
    ) {}

    /**
     * Get form data for creation/edit.
     */
    public function getFormData(): array
    {
        return [
            'tenants' => $this->tenantRepository->getAllGlobal(['is_active' => true], ['name' => 'asc']),
            'plans' => $this->planRepository->getAllGlobal(['is_active' => true], ['name' => 'asc']),
            'professions' => $this->professionRepository->getAll(['is_active' => true], ['name' => 'asc']),
            'areasOfActivity' => $this->areaOfActivityRepository->getAll(['is_active' => true], ['name' => 'asc']),
        ];
    }

    /**
     * Get paginated providers for admin with filters.
     */
    public function getProvidersPaginated(array $filters, int $perPage = 25): ServiceResult
    {
        return $this->safeExecute(function () use ($filters, $perPage) {
            $providers = $this->providerRepository->getAdminPaginated($filters, $perPage);
            $tenants = $this->tenantRepository->getAllGlobal(['is_active' => true], ['name' => 'asc']);
            $statistics = $this->getStatistics();

            return ServiceResult::success([
                'providers' => $providers,
                'tenants' => $tenants,
                'statistics' => $statistics,
            ]);
        }, 'Erro ao listar fornecedores.');
    }

    /**
     * Get provider statistics.
     */
    public function getStatistics(): array
    {
        return $this->cacheService->remember('admin.providers.statistics', 300, function () {
            return $this->providerRepository->getGlobalStatistics();
        });
    }

    /**
     * Toggle provider active status.
     */
    public function toggleStatus(int $providerId): ServiceResult
    {
        return $this->safeExecute(function () use ($providerId) {
            $provider = $this->providerRepository->findGlobalWithRelations($providerId);

            if (! $provider) {
                return ServiceResult::error(404, 'Fornecedor não encontrado.');
            }

            $provider->is_active = ! $provider->is_active;
            $provider->save();

            $this->cacheService->forgetPattern('providers.*');
            $this->cacheService->forget('admin.providers.statistics');

            Log::info('Provider status toggled by admin', [
                'provider_id' => $provider->id,
                'new_status' => $provider->is_active ? 'active' : 'inactive',
                'admin_id' => Auth::id(),
            ]);

            return ServiceResult::success($provider, 'Status alterado com sucesso!');
        }, 'Erro ao alterar status do fornecedor.');
    }

    /**
     * Delete a provider.
     */
    public function deleteProvider(int $providerId): ServiceResult
    {
        return $this->safeExecute(function () use ($providerId) {
            $provider = $this->providerRepository->findWithRelations($providerId, [
                'customers',
                'budgets',
                'services',
                'invoices',
            ]);

            if (! $provider) {
                return ServiceResult::error(404, 'Fornecedor não encontrado.');
            }

            // Check dependencies
            if ($provider->customers_count > 0 || $provider->customers()->exists()) {
                return ServiceResult::error(422, 'Não é possível excluir um fornecedor que possui clientes associados.');
            }

            if ($provider->budgets_count > 0 || $provider->budgets()->exists()) {
                return ServiceResult::error(422, 'Não é possível excluir um fornecedor que possui orçamentos associados.');
            }

            if ($provider->services_count > 0 || $provider->services()->exists()) {
                return ServiceResult::error(422, 'Não é possível excluir um fornecedor que possui serviços associados.');
            }

            if ($provider->invoices_count > 0 || $provider->invoices()->exists()) {
                return ServiceResult::error(422, 'Não é possível excluir um fornecedor que possui faturas associadas.');
            }

            return DB::transaction(function () use ($provider) {
                $providerName = $provider->name;
                $provider->delete();

                $this->cacheService->forgetPattern('providers.*');
                $this->cacheService->forget('admin.providers.statistics');

                Log::info('Provider deleted by admin', [
                    'provider_name' => $providerName,
                    'admin_id' => Auth::id(),
                ]);

                return ServiceResult::success(null, 'Fornecedor excluído com sucesso!');
            });
        }, 'Erro ao excluir fornecedor.');
    }

    /**
     * Get detailed statistics for a specific provider.
     */
    public function getProviderDetails(int $providerId): ServiceResult
    {
        return $this->safeExecute(function () use ($providerId) {
            $provider = $this->providerRepository->findWithRelations($providerId, [
                'tenant',
                'user',
                'commonData',
                'address',
                'contact',
                'businessData',
                'planSubscriptions.plan',
                'customers',
                'budgets',
                'services',
                'invoices',
            ]);

            if (! $provider) {
                return ServiceResult::error(404, 'Fornecedor não encontrado.');
            }

            $statistics = [
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

            $recentInteractions = [
                'customers' => $statistics['recent_customers'],
                'budgets' => $statistics['recent_budgets'],
                'services' => $statistics['recent_services'],
                'invoices' => $statistics['recent_invoices'],
            ];

            return ServiceResult::success([
                'provider' => $provider,
                'statistics' => $statistics,
                'recentInteractions' => $recentInteractions,
                'formData' => $this->getFormData(),
            ]);
        }, 'Erro ao carregar detalhes do fornecedor.');
    }

    /**
     * Store a new provider.
     */
    public function storeProvider(ProviderDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            return DB::transaction(function () use ($dto) {
                // 1. Create User
                $userData = $dto->user->toArray();
                if (! isset($userData['password'])) {
                    $userData['password'] = bcrypt(Str::random(12));
                }
                $user = $this->userRepository->create($userData);

                // 2. Create Provider
                $providerData = $dto->toArray();
                $providerData['user_id'] = $user->id;
                $provider = $this->providerRepository->create($providerData);

                // 3. Create CommonData
                if ($dto->common_data) {
                    $commonData = $dto->common_data->toArray();
                    $commonData['provider_id'] = $provider->id;
                    $commonData['tenant_id'] = $dto->tenant_id;
                    $this->commonDataRepository->create($commonData);
                }

                // 4. Create Address
                if ($dto->address) {
                    $addressData = $dto->address->toArray();
                    $addressData['provider_id'] = $provider->id;
                    $addressData['tenant_id'] = $dto->tenant_id;
                    $this->addressRepository->create($addressData);
                }

                // 5. Create Contact
                if ($dto->contact) {
                    $contactData = $dto->contact->toArray();
                    $contactData['provider_id'] = $provider->id;
                    $contactData['tenant_id'] = $dto->tenant_id;
                    $this->contactRepository->create($contactData);
                }

                $this->cacheService->forget('admin.providers.statistics');

                return ServiceResult::success($provider, 'Fornecedor criado com sucesso!');
            });
        }, 'Erro ao criar fornecedor.');
    }

    /**
     * Update an existing provider.
     */
    public function updateProvider(int $id, ProviderDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $dto) {
            $provider = $this->providerRepository->findWithRelations($id, ['user', 'commonData', 'address', 'contact']);

            if (! $provider) {
                return ServiceResult::error(404, 'Fornecedor não encontrado.');
            }

            return DB::transaction(function () use ($provider, $dto) {
                // 1. Update User
                if ($dto->user) {
                    $this->userRepository->update($provider->user_id, $dto->user->toArray());
                }

                // 2. Update Provider
                $this->providerRepository->update($provider->id, $dto->toArray());

                // 3. Update/Create CommonData
                if ($dto->common_data) {
                    if ($provider->commonData) {
                        $this->commonDataRepository->update($provider->commonData->id, $dto->common_data->toArray());
                    } else {
                        $commonData = $dto->common_data->toArray();
                        $commonData['provider_id'] = $provider->id;
                        $commonData['tenant_id'] = $provider->tenant_id;
                        $this->commonDataRepository->create($commonData);
                    }
                }

                // 4. Update/Create Address
                if ($dto->address) {
                    if ($provider->address) {
                        $this->addressRepository->update($provider->address->id, $dto->address->toArray());
                    } else {
                        $addressData = $dto->address->toArray();
                        $addressData['provider_id'] = $provider->id;
                        $addressData['tenant_id'] = $provider->tenant_id;
                        $this->addressRepository->create($addressData);
                    }
                }

                // 5. Update/Create Contact
                if ($dto->contact) {
                    if ($provider->contact) {
                        $this->contactRepository->update($provider->contact->id, $dto->contact->toArray());
                    } else {
                        $contactData = $dto->contact->toArray();
                        $contactData['provider_id'] = $provider->id;
                        $contactData['tenant_id'] = $provider->tenant_id;
                        $this->contactRepository->create($contactData);
                    }
                }

                $this->cacheService->forget('admin.providers.statistics');
                $this->cacheService->forgetPattern("providers.{$provider->id}*");

                return ServiceResult::success($provider, 'Fornecedor atualizado com sucesso!');
            });
        }, 'Erro ao atualizar fornecedor.');
    }

    /**
     * Get providers for export.
     */
    public function getExportData(array $filters): array
    {
        return $this->providerRepository->getAdminPaginated($filters, 1000)->items();
    }

    /**
     * Get providers for a specific tenant (for API/JSON usage).
     */
    public function getProvidersByTenant(int $tenantId, ?string $search = null): array
    {
        return $this->providerRepository->getByTenantWithSearch($tenantId, $search);
    }
}
