<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Actions\Provider\RegisterProviderAction;
use App\Actions\Provider\UpdateProviderAction;
use App\DTOs\Provider\ProviderRegistrationDTO;
use App\DTOs\Provider\ProviderUpdateDTO;
use App\DTOs\User\UserDTO;
use App\Enums\OperationStatus;
use App\Models\Provider;
use App\Repositories\AddressRepository;
use App\Repositories\AreaOfActivityRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\BudgetRepository;
use App\Repositories\BusinessDataRepository;
use App\Repositories\CommonDataRepository;
use App\Repositories\ContactRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\PlanRepository;
use App\Repositories\PlanSubscriptionRepository;
use App\Repositories\ProfessionRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\RoleRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use App\Services\Core\Traits\HasSafeExecution;
use App\Services\Core\Traits\HasTenantIsolation;
use App\Services\Domain\ProviderService;
use App\Services\Infrastructure\FileUploadService;
use App\Services\Infrastructure\FinancialSummary;
use App\Services\Shared\EntityDataService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// Constants for magic strings to improve maintainability
const ROLE_PROVIDER = 'provider';
const PLAN_SLUG_TRIAL = 'trial';
const SUBSCRIPTION_STATUS_ACTIVE = 'active';
const PAYMENT_METHOD_TRIAL = 'trial';
const TRIAL_DAYS = 7;

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
        private \App\Repositories\InventoryRepository $inventoryRepository,
        private RegisterProviderAction $registerProviderAction,
        private UpdateProviderAction $updateProviderAction,
    ) {}

    /**
     * Get provider dashboard data.
     */
    public function getDashboardData(): ServiceResult
    {
        return $this->safeExecute(function () {
            $user = Auth::user();

            // Buscar orçamentos recentes
            $budgets = $this->budgetRepository->getRecentBudgets(10);

            // Buscar atividades recentes
            $activities = $this->auditLogRepository->getRecentActivities($user->id, 10);

            // Buscar resumo financeiro
            $financialResult = $this->financialSummary->getMonthlySummary($user->tenant_id ?? $this->ensureTenantId());
            $financialSummary = $financialResult->isSuccess() ? $financialResult->getData() : [];

            // Buscar compromissos da semana
            $events = $this->scheduleRepository->getWeeklyEvents(5);

            // Buscar itens com estoque baixo
            $lowStockItems = $this->inventoryRepository->getLowStockItems(5);
            $lowStockCount = $this->inventoryRepository->getLowStockCount();

            return ServiceResult::success([
                'budgets' => $budgets,
                'activities' => $activities,
                'financial_summary' => $financialSummary,
                'events' => $events,
                'low_stock_items' => $lowStockItems,
                'low_stock_count' => $lowStockCount,
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

            $provider = $this->providerService->getByUserId($user->id);

            if (! $provider) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Provider não encontrado');
            }

            return ServiceResult::success([
                'provider' => $provider,
                'areas_of_activity' => $this->areaOfActivityRepository->getAll(),
                'professions' => $this->professionRepository->getAll(),
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
                'businessData',
            ]);

            if (! $provider) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Provider não encontrado');
            }

            $updatedProvider = $this->updateProviderAction->execute($provider, $user, $dto, $tenantId);

            return ServiceResult::success($updatedProvider, 'Provider atualizado com sucesso');
        }, 'Erro ao atualizar provider.');
    }

    /**
     * Change provider password.
     */
    public function changePassword(string $newPassword): ServiceResult
    {
        return $this->safeExecute(function () use ($newPassword) {
            $user = Auth::user();
            $this->userRepository->updateFromDTO($user->id, new UserDTO(
                name: $user->name,
                email: $user->email,
                tenant_id: $user->tenant_id,
                password: Hash::make($newPassword)
            ));

            return ServiceResult::success(null, 'Senha alterada com sucesso.');
        }, 'Erro ao alterar senha.');
    }

    /**
     * Get provider by user ID.
     */
    public function getProviderByUserId(int $userId): ?Provider
    {
        return $this->providerService->getByUserId($userId);
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
                'monthly_revenue' => $monthlyRevenue,
                'pending_budgets' => $pendingBudgets,
                'overdue_payments' => $overduePayments,
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
                'total_budgets' => $budgets->count(),
                'approved_budgets' => $budgets->where('status', 'approved')->count(),
                'pending_budgets' => $budgets->where('status', 'pending')->count(),
                'rejected_budgets' => $budgets->where('status', 'rejected')->count(),
                'total_value' => $budgets->sum('total'),
                'average_value' => $budgets->count() > 0 ? $budgets->avg('total') : 0,
            ];

            return ServiceResult::success([
                'budgets' => $budgets,
                'budget_stats' => $budgetStats,
                'period' => now()->format('F Y'),
            ]);
        }, 'Erro ao obter relatórios de orçamentos.');
    }

    /**
     * Get service reports data for provider.
     */
    public function getServiceReports(): ServiceResult
    {
        return $this->safeExecute(function () {
            // Buscar serviços do mês atual
            $services = $this->serviceRepository->getServicesByMonth(now()->month, now()->year);

            // Estatísticas dos serviços
            $serviceStats = [
                'total_services' => $services->count(),
                'completed_services' => $services->where('status', 'completed')->count(),
                'pending_services' => $services->where('status', 'pending')->count(),
                'cancelled_services' => $services->where('status', 'cancelled')->count(),
                'total_value' => $services->sum('total'),
                'average_value' => $services->count() > 0 ? $services->avg('total') : 0,
            ];

            return ServiceResult::success([
                'services' => $services,
                'service_stats' => $serviceStats,
                'period' => now()->format('F Y'),
            ]);
        }, 'Erro ao obter relatórios de serviços.');
    }

    /**
     * Get customer reports data for provider.
     */
    public function getCustomerReports(): ServiceResult
    {
        return $this->safeExecute(function () {
            // Buscar clientes ativos
            $customers = $this->customerRepository->getActiveWithStats(50);

            // Estatísticas dos clientes
            $customerStats = [
                'total_customers' => $customers->count(),
                'active_customers' => $customers->where('status', 'active')->count(),
                'inactive_customers' => $customers->where('status', 'inactive')->count(),
                'new_customers_month' => $customers->filter(function ($customer) {
                    return $customer->created_at->month === now()->month &&
                        $customer->created_at->year === now()->year;
                })->count(),
                'total_budgets' => $customers->sum(fn ($c) => $c->budgets_count ?? $c->budgets->count()),
                'total_invoices' => $customers->sum(fn ($c) => $c->invoices_count ?? $c->invoices->count()),
            ];

            return ServiceResult::success([
                'customers' => $customers,
                'customer_stats' => $customerStats,
                'period' => now()->format('F Y'),
            ]);
        }, 'Erro ao obter relatórios de clientes.');
    }

    /**
     * Create complete registration from user data using Action.
     */
    public function createProviderFromRegistration(ProviderRegistrationDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $data = $this->registerProviderAction->execute($dto);

            return ServiceResult::success($data, 'Registro completo realizado com sucesso.');
        }, 'Erro ao criar registro do provedor.');
    }
}
