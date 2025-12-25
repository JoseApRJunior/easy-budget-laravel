<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Enums\OperationStatus;
use App\Models\CommonData;
use App\Models\PlanSubscription;
use App\Models\Provider;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\AreaOfActivityRepository;
use App\Repositories\ProfessionRepository;
use App\Repositories\CommonDataRepository;
use App\Repositories\PlanRepository;
use App\Repositories\PlanSubscriptionRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\RoleRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use App\Repositories\BudgetRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\ContactRepository;
use App\Repositories\AddressRepository;
use App\Repositories\BusinessDataRepository;
use App\Services\Domain\ProviderService;
use App\Services\Infrastructure\FileUploadService;
use App\Services\Infrastructure\FinancialSummary;
use App\Services\Shared\EntityDataService;
use App\Support\ServiceResult;
use App\DTOs\Provider\ProviderUpdateDTO;
use App\DTOs\Provider\ProviderRegistrationDTO;
use App\Services\Core\Traits\HasSafeExecution;
use App\Services\Core\Traits\HasTenantIsolation;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Constants for magic strings to improve maintainability
const ROLE_PROVIDER              = 'provider';
const PLAN_SLUG_TRIAL            = 'trial';
const SUBSCRIPTION_STATUS_ACTIVE = 'active';
const PAYMENT_METHOD_TRIAL       = 'trial';
const TRIAL_DAYS                 = 7;

class ProviderManagementService
{
    use HasSafeExecution, HasTenantIsolation;

    public function __construct(
        private FinancialSummary $financialSummary,
        private ProviderService $providerService,
        private EntityDataService $entityDataService,
        private FileUploadService $fileUploadService,
        private CommonDataRepository $commonDataRepository,
        private ProviderRepository $providerRepository,
        private PlanRepository $planRepository,
        private RoleRepository $roleRepository,
        private TenantRepository $tenantRepository,
        private UserRepository $userRepository,
        private BudgetRepository $budgetRepository,
        private AuditLogRepository $auditLogRepository,
        private ScheduleRepository $scheduleRepository,
        private CustomerRepository $customerRepository,
        private ServiceRepository $serviceRepository,
        private ContactRepository $contactRepository,
        private AddressRepository $addressRepository,
        private BusinessDataRepository $businessDataRepository,
        private AreaOfActivityRepository $areaOfActivityRepository,
        private ProfessionRepository $professionRepository,
        private PlanSubscriptionRepository $planSubscriptionRepository,
    ) {}

    /**
     * Get provider dashboard data.
     */
    public function getDashboardData(): ServiceResult
    {
        return $this->safeExecute(function () {
            $tenantId = $this->ensureTenantId();
            $user = Auth::user();

            // Buscar orçamentos recentes
            $budgets = $this->budgetRepository->getRecentBudgets($tenantId, 10);

            // Buscar atividades recentes
            $activities = $this->auditLogRepository->getRecentActivities($tenantId, $user->id, 10);

            // Buscar resumo financeiro
            $financialResult = $this->financialSummary->getMonthlySummary($tenantId);
            $financialSummary = $financialResult->isSuccess() ? $financialResult->getData() : [];

            // Buscar compromissos do dia
            $events = $this->scheduleRepository->getTodayEvents($tenantId, 5);

            return ServiceResult::success([
                'budgets'           => $budgets,
                'activities'        => $activities,
                'financial_summary' => $financialSummary,
                'events'            => $events,
            ]);
        }, 'Erro ao obter dados do dashboard do provedor.');
    }

    /**
     * Get provider data for update form.
     */
    public function getProviderForUpdate(): ServiceResult
    {
        return $this->safeExecute(function () {
            $user = Auth::user();
            $tenantId = $this->ensureTenantId();

            $provider = $this->providerService->getByUserId($user->id, $tenantId);

            if (!$provider) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Provider não encontrado');
            }

            return ServiceResult::success([
                'provider'          => $provider,
                'areas_of_activity' => $this->areaOfActivityRepository->getAll(),
                'professions'       => $this->professionRepository->getAll(),
            ]);
        }, 'Erro ao carregar dados para atualização do provedor.');
    }

    /**
     * Update provider data.
     */
    public function updateProvider(ProviderUpdateDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $user = Auth::user();
            $tenantId = $this->ensureTenantId();

            $provider = $this->providerRepository->findWithRelations($user->provider->id, [
                'commonData',
                'contact',
                'address',
                'businessData'
            ]);

            if (!$provider) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Provider não encontrado');
            }

            return DB::transaction(function () use ($provider, $dto, $user, $tenantId) {
                // Handle logo upload
                $logoPath = $user->logo;
                if ($dto->logo instanceof UploadedFile) {
                    $logoPath = $this->fileUploadService->uploadProviderLogo($dto->logo, $user->logo);
                }

                // Update User (email and logo only)
                $this->userRepository->update($user->id, array_filter([
                    'email' => $dto->email,
                    'logo'  => $logoPath,
                ], fn($value) => $value !== null));

                // Detectar tipo (PF ou PJ)
                $type = !empty($dto->cnpj) ? CommonData::TYPE_COMPANY : CommonData::TYPE_INDIVIDUAL;

                // Limpar máscaras e converter datas
                $cpf = !empty($dto->cpf) ? preg_replace('/\D/', '', $dto->cpf) : null;
                $cnpj = !empty($dto->cnpj) ? preg_replace('/\D/', '', $dto->cnpj) : null;
                $birthDate = !empty($dto->birth_date) ? Carbon::createFromFormat('d/m/Y', $dto->birth_date)->format('Y-m-d') : null;

                // Atualizar CommonData
                if ($provider->commonData) {
                    $this->commonDataRepository->update($provider->commonData->id, [
                        'type'                => $type,
                        'first_name'          => $dto->first_name,
                        'last_name'           => $dto->last_name,
                        'cpf'                 => $cpf,
                        'birth_date'          => $birthDate,
                        'company_name'        => $dto->company_name,
                        'cnpj'                => $cnpj,
                        'description'         => $dto->description,
                        'area_of_activity_id' => $dto->area_of_activity_id,
                        'profession_id'       => $dto->profession_id,
                    ]);
                }

                // Atualizar Contact
                if ($provider->contact) {
                    $this->contactRepository->update($provider->contact->id, [
                        'email_personal' => $dto->email_personal ?? $dto->email,
                        'phone_personal' => $dto->phone_personal,
                        'email_business' => $dto->email_business,
                        'phone_business' => $dto->phone_business,
                        'website'        => $dto->website,
                    ]);
                }

                // Atualizar Address
                if ($provider->address) {
                    $this->addressRepository->update($provider->address->id, [
                        'address'        => $dto->address,
                        'address_number' => $dto->address_number,
                        'neighborhood'   => $dto->neighborhood,
                        'city'           => $dto->city,
                        'state'          => $dto->state,
                        'cep'            => $dto->cep,
                    ]);
                }

                // Atualizar dados empresariais
                if ($type === CommonData::TYPE_COMPANY) {
                    $foundingDate = !empty($dto->founding_date) ? Carbon::createFromFormat('d/m/Y', $dto->founding_date)->format('Y-m-d') : null;
                    $businessData = [
                        'fantasy_name'           => $dto->fantasy_name,
                        'state_registration'     => $dto->state_registration,
                        'municipal_registration' => $dto->municipal_registration,
                        'founding_date'          => $foundingDate,
                        'industry'               => $dto->industry,
                        'company_size'           => $dto->company_size,
                        'notes'                  => $dto->notes,
                    ];

                    if ($provider->businessData) {
                        $this->businessDataRepository->update($provider->businessData->id, $businessData);
                    } else {
                        $this->businessDataRepository->create(array_merge($businessData, [
                            'tenant_id'   => $tenantId,
                            'provider_id' => $provider->id,
                        ]));
                    }
                }

                $provider->refresh();
                return ServiceResult::success($provider, 'Provider atualizado com sucesso');
            });
        }, 'Erro ao atualizar provider.');
    }

    /**
     * Change provider password.
     */
    public function changePassword(string $newPassword): ServiceResult
    {
        return $this->safeExecute(function () use ($newPassword) {
            $user = Auth::user();
            $this->userRepository->update($user->id, [
                'password' => Hash::make($newPassword)
            ]);
            return ServiceResult::success(null, 'Senha alterada com sucesso.');
        }, 'Erro ao alterar senha.');
    }

    /**
     * Get provider by user ID.
     */
    public function getProviderByUserId(int $userId, ?int $tenantId = null): ?Provider
    {
        $tenantId = $tenantId ?? $this->ensureTenantId();
        return $this->providerService->getByUserId($userId, $tenantId);
    }

    /**
     * Check if email exists for another user.
     */
    public function isEmailAvailable(string $email, int $excludeUserId, ?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? $this->ensureTenantId();
        return $this->providerService->isEmailAvailable($email, $excludeUserId, $tenantId);
    }

    /**
     * Get financial reports data for provider.
     */
    public function getFinancialReports(): ServiceResult
    {
        return $this->safeExecute(function () {
            $tenantId = $this->ensureTenantId();

            // Buscar resumo financeiro mensal
            $financialResult = $this->financialSummary->getMonthlySummary($tenantId);
            $financialSummary = $financialResult->isSuccess() ? $financialResult->getData() : [];

            // Buscar receitas mensais
            $monthlyRevenue = $this->budgetRepository->getMonthlyRevenue($tenantId, now()->month, now()->year);

            // Buscar orçamentos pendentes
            $pendingBudgets = $this->budgetRepository->getPendingBudgets($tenantId, 10);

            // Buscar pagamentos em atraso
            $overduePayments = $this->budgetRepository->getOverduePayments($tenantId, 10);

            return ServiceResult::success([
                'financial_summary' => $financialSummary,
                'monthly_revenue'   => $monthlyRevenue,
                'pending_budgets'   => $pendingBudgets,
                'overdue_payments'  => $overduePayments,
            ]);
        }, 'Erro ao obter relatórios financeiros.');
    }

    /**
     * Get budget reports data for provider.
     */
    public function getBudgetReports(): ServiceResult
    {
        return $this->safeExecute(function () {
            $tenantId = $this->ensureTenantId();

            // Buscar orçamentos do mês atual
            $budgets = $this->budgetRepository->getBudgetsByMonth($tenantId, now()->month, now()->year);

            // Estatísticas dos orçamentos
            $budgetStats = [
                'total_budgets'    => $budgets->count(),
                'approved_budgets' => $budgets->where('status', 'approved')->count(),
                'pending_budgets'  => $budgets->where('status', 'pending')->count(),
                'rejected_budgets' => $budgets->where('status', 'rejected')->count(),
                'total_value'      => $budgets->sum('total'),
                'average_value'    => $budgets->count() > 0 ? $budgets->avg('total') : 0,
            ];

            return ServiceResult::success([
                'budgets'      => $budgets,
                'budget_stats' => $budgetStats,
                'period'       => now()->format('F Y'),
            ]);
        }, 'Erro ao obter relatórios de orçamentos.');
    }

    /**
     * Get service reports data for provider.
     */
    public function getServiceReports(): ServiceResult
    {
        return $this->safeExecute(function () {
            $tenantId = $this->ensureTenantId();

            // Buscar serviços do mês atual
            $services = $this->serviceRepository->getServicesByMonth($tenantId, now()->month, now()->year);

            // Estatísticas dos serviços
            $serviceStats = [
                'total_services'     => $services->count(),
                'completed_services' => $services->where('status', 'completed')->count(),
                'pending_services'   => $services->where('status', 'pending')->count(),
                'cancelled_services' => $services->where('status', 'cancelled')->count(),
                'total_value'        => $services->sum('total'),
                'average_value'      => $services->count() > 0 ? $services->avg('total') : 0,
            ];

            return ServiceResult::success([
                'services'      => $services,
                'service_stats' => $serviceStats,
                'period'        => now()->format('F Y'),
            ]);
        }, 'Erro ao obter relatórios de serviços.');
    }

    /**
     * Get customer reports data for provider.
     */
    public function getCustomerReports(): ServiceResult
    {
        return $this->safeExecute(function () {
            $tenantId = $this->ensureTenantId();

            // Buscar clientes ativos
            $customers = $this->customerRepository->getActiveWithStats($tenantId, 50);

            // Estatísticas dos clientes
            $customerStats = [
                'total_customers'     => $customers->count(),
                'active_customers'    => $customers->where('status', 'active')->count(),
                'inactive_customers'  => $customers->where('status', 'inactive')->count(),
                'new_customers_month' => $customers->filter(function ($customer) {
                    return $customer->created_at->month === now()->month &&
                        $customer->created_at->year === now()->year;
                })->count(),
                'total_budgets'       => $customers->sum(fn($c) => $c->budgets_count ?? $c->budgets->count()),
                'total_invoices'      => $customers->sum(fn($c) => $c->invoices_count ?? $c->invoices->count()),
            ];

            return ServiceResult::success([
                'customers'      => $customers,
                'customer_stats' => $customerStats,
                'period'         => now()->format('F Y'),
            ]);
        }, 'Erro ao obter relatórios de clientes.');
    }

    /**
     * Create complete registration from user data.
     */
    public function createProviderFromRegistration(ProviderRegistrationDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            return DB::transaction(function () use ($dto) {
                $userData = $dto->toArray();

                // Step 1: Create Tenant
                $tenantResult = $this->createTenant($userData);
                if (!$tenantResult->isSuccess()) {
                    throw new Exception($tenantResult->getMessage());
                }
                $tenant = $tenantResult->getData();

                // Step 2: Create User
                $userResult = $this->createUser($userData, $tenant);
                if (!$userResult->isSuccess()) {
                    throw new Exception($userResult->getMessage());
                }
                $user = $userResult->getData();

                // Step 3: Create Provider with all related data
                $providerResult = $this->createProviderWithRelatedData($userData, $user, $tenant);
                if (!$providerResult->isSuccess()) {
                    throw new Exception($providerResult->getMessage());
                }

                $providerData = $providerResult->getData();

                return ServiceResult::success([
                    'user'         => $user,
                    'tenant'       => $tenant,
                    'provider'     => $providerData['provider'],
                    'plan'         => $providerData['plan'],
                    'subscription' => $providerData['subscription'],
                ], 'Registro completo realizado com sucesso.');
            });
        }, 'Erro ao criar registro do provedor.');
    }

    /**
     * Create provider with all related data.
     */
    private function createProviderWithRelatedData(array $userData, User $user, Tenant $tenant): ServiceResult
    {
        try {
            // Criar Provider primeiro
            $provider = $this->providerRepository->create([
                'tenant_id'      => $tenant->id,
                'user_id'        => $user->id,
                'terms_accepted' => $userData['terms_accepted'],
            ]);

            // Criar CommonData vinculado ao Provider
            $this->commonDataRepository->create([
                'tenant_id'   => $tenant->id,
                'provider_id' => $provider->id,
                'type'        => CommonData::TYPE_INDIVIDUAL,
                'first_name'  => $userData['first_name'],
                'last_name'   => $userData['last_name'],
            ]);

            // Criar Contact vinculado ao Provider
            $this->contactRepository->create([
                'tenant_id'      => $tenant->id,
                'provider_id'    => $provider->id,
                'email_personal' => $userData['email_personal'] ?? $userData['email'],
                'phone_personal' => $userData['phone_personal'] ?? $userData['phone'] ?? null,
            ]);

            // Criar Address vinculado ao Provider
            $this->addressRepository->create([
                'tenant_id'   => $tenant->id,
                'provider_id' => $provider->id,
            ]);

            // Assign Provider Role
            $providerRole = $this->roleRepository->findByName(ROLE_PROVIDER);

            if (!$providerRole) {
                return ServiceResult::error(OperationStatus::ERROR, 'Role provider não encontrado.');
            }

            $user->roles()->syncWithoutDetaching([
                $providerRole->id => [
                    'tenant_id'  => $tenant->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // Find Trial Plan
            $plan = $this->planRepository->findBySlug(PLAN_SLUG_TRIAL);
            if (!$plan) {
                // Se não encontrar por slug, busca qualquer plano ativo com preço 0
                $plan = $this->planRepository->findFreeActive();
            }

            if (!$plan) {
                return ServiceResult::error(OperationStatus::ERROR, 'Plano trial não encontrado.');
            }

            // Create Plan Subscription
            $savedSubscription = $this->planSubscriptionRepository->create([
                'tenant_id'          => $tenant->id,
                'plan_id'            => $plan->id,
                'provider_id'        => $provider->id,
                'status'             => SUBSCRIPTION_STATUS_ACTIVE,
                'transaction_amount' => $plan->price ?? 0.00,
                'start_date'         => now(),
                'end_date'           => now()->addDays(TRIAL_DAYS),
                'transaction_date'   => now(),
                'payment_method'     => PAYMENT_METHOD_TRIAL,
                'payment_id'         => 'TRIAL_' . uniqid(),
                'public_hash'        => 'TRIAL_HASH_' . uniqid(),
            ]);

            return ServiceResult::success([
                'provider'     => $provider,
                'role'         => $providerRole,
                'plan'         => $plan,
                'subscription' => $savedSubscription,
            ]);
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao criar dados vinculados: ' . $e->getMessage());
        }
    }

    /**
     * Cria um tenant único para o usuário.
     */
    private function createTenant(array $userData): ServiceResult
    {
        try {
            $tenantName = $this->generateUniqueTenantName($userData['first_name'], $userData['last_name']);

            $tenant = $this->tenantRepository->create([
                'name'      => $tenantName,
                'is_active' => true,
            ]);

            return ServiceResult::success($tenant);
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao criar tenant: ' . $e->getMessage());
        }
    }

    /**
     * Cria um usuário no sistema.
     */
    private function createUser(array $userData, Tenant $tenant): ServiceResult
    {
        try {
            $savedUser = $this->userRepository->create([
                'tenant_id' => $tenant->id,
                'email'     => $userData['email'],
                'password'  => $userData['password'] ?? null,
                'is_active' => true,
            ]);

            return ServiceResult::success($savedUser);
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao criar usuário: ' . $e->getMessage());
        }
    }

    /**
     * Gera um nome único para o tenant.
     */
    private function generateUniqueTenantName(string $firstName, string $lastName): string
    {
        $baseName   = Str::slug($firstName . '-' . $lastName);
        $tenantName = $baseName;
        $counter    = 1;

        while ($this->tenantRepository->findByName($tenantName)) {
            $tenantName = $baseName . '-' . $counter;
            $counter++;
        }

        return $tenantName;
    }
}
