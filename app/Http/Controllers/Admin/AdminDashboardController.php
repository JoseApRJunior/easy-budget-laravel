<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Provider;
use App\Models\Tenant;
use App\Services\ChartService;
use App\Services\MetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(
        private ChartService $chartService,
        private MetricsService $metricsService,
    ) {}

    /**
     * Admin dashboard with global system metrics
     */
    public function index(Request $request): View
    {
        $period = $request->get('period', 'month');
        $cacheKey = "admin.dashboard.global.{$period}";
        $ttl = 300; // 5 minutes cache for admin data

        $dashboardData = Cache::remember($cacheKey, $ttl, function () use ($period) {
            return [
                'system_metrics' => $this->getSystemMetrics($period),
                'financial_metrics' => $this->getFinancialMetrics($period),
                'user_metrics' => $this->getUserMetrics($period),
                'plan_metrics' => $this->getPlanMetrics($period),
                'recent_activities' => $this->getRecentSystemActivities(),
                'charts' => $this->getAdminCharts($period),
                'alerts' => $this->getSystemAlerts(),
            ];
        });

        return view('pages.admin.home', [
            'systemMetrics' => $dashboardData['system_metrics'],
            'financialMetrics' => $dashboardData['financial_metrics'],
            'userMetrics' => $dashboardData['user_metrics'],
            'planMetrics' => $dashboardData['plan_metrics'],
            'recentActivities' => $dashboardData['recent_activities'],
            'charts' => $dashboardData['charts'],
            'alerts' => $dashboardData['alerts'],
            'currentPeriod' => $period,
            'lastUpdated' => Carbon::now()->toDateTimeString(),
        ]);
    }

    /**
     * Get system-wide metrics
     */
    private function getSystemMetrics(string $period): array
    {
        $startDate = $this->getPeriodStartDate($period);

        return [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'trial_tenants' => $this->getTrialTenantsCount(),
            'suspended_tenants' => Tenant::where('is_active', false)->count(),
            'new_tenants_period' => Tenant::where('created_at', '>=', $startDate)->count(),
            'system_uptime' => $this->getSystemUptime(),
            'database_size' => $this->getDatabaseSize(),
            'total_storage_used' => $this->getTotalStorageUsed(),
        ];
    }

    /**
     * Get financial metrics
     */
    private function getFinancialMetrics(string $period): array
    {
        $startDate = $this->getPeriodStartDate($period);

        // Admin global precisa ver dados de todos os tenants
        $monthlyRevenue = PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
            ->where('status', 'active')
            ->where('created_at', '>=', $startDate)
            ->sum('transaction_amount');

        $projectedRevenue = $this->calculateProjectedRevenue();

        $totalInvoices = Invoice::where('created_at', '>=', $startDate)->count();
        $totalInvoiceValue = Invoice::where('created_at', '>=', $startDate)->sum('total');

        return [
            'monthly_revenue' => $monthlyRevenue,
            'projected_revenue' => $projectedRevenue,
            'total_invoices_period' => $totalInvoices,
            'total_invoice_value_period' => $totalInvoiceValue,
            'average_invoice_value' => $totalInvoices > 0 ? $totalInvoiceValue / $totalInvoices : 0,
            'revenue_growth' => $this->calculateRevenueGrowth($period),
        ];
    }

    /**
     * Get user metrics
     */
    private function getUserMetrics(string $period): array
    {
        $startDate = $this->getPeriodStartDate($period);

        $totalCustomers = Customer::count();
        $totalProviders = Provider::count();
        $newCustomersPeriod = Customer::where('created_at', '>=', $startDate)->count();
        $newProvidersPeriod = Provider::where('created_at', '>=', $startDate)->count();

        $providerRetention = $this->calculateProviderRetention($period);

        return [
            'total_customers' => $totalCustomers,
            'total_providers' => $totalProviders,
            'new_customers_period' => $newCustomersPeriod,
            'new_providers_period' => $newProvidersPeriod,
            'provider_retention_rate' => $providerRetention,
            'customer_growth_rate' => $this->calculateGrowthRate(Customer::class, $period),
            'provider_growth_rate' => $this->calculateGrowthRate(Provider::class, $period),
        ];
    }

    /**
     * Get plan metrics
     */
    private function getPlanMetrics(string $period): array
    {
        $startDate = $this->getPeriodStartDate($period);

        $planDistribution = Plan::withCount(['planSubscriptions' => function ($query) {
            $query->where('status', 'active');
        }])->get();

        // Calcular upgrades/downgrades baseado em mudanças de plano por tenant
        $planUpgrades = 0;
        $planDowngrades = 0;

        // Admin global precisa ver dados de todos os tenants
        $recentSubscriptions = PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
            ->where('created_at', '>=', $startDate)
            ->where('status', 'active')
            ->get();

        foreach ($recentSubscriptions->groupBy('tenant_id') as $tenantId => $subscriptions) {
            if ($subscriptions->count() > 1) {
                $latest = $subscriptions->sortByDesc('created_at')->first();
                $previous = $subscriptions->sortByDesc('created_at')->skip(1)->first();

                if ($latest && $previous && $latest->plan_id !== $previous->plan_id) {
                    $latestPlan = Plan::find($latest->plan_id);
                    $previousPlan = Plan::find($previous->plan_id);

                    if ($latestPlan && $previousPlan) {
                        if ($latestPlan->price > $previousPlan->price) {
                            $planUpgrades++;
                        } elseif ($latestPlan->price < $previousPlan->price) {
                            $planDowngrades++;
                        }
                    }
                }
            }
        }

        return [
            'total_plans' => Plan::count(),
            // Admin global precisa ver dados de todos os tenants
            'active_subscriptions' => PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
                ->where('status', 'active')->count(),
            'plan_distribution' => $planDistribution,
            'plan_upgrades_period' => $planUpgrades,
            'plan_downgrades_period' => $planDowngrades,
            'plan_churn_rate' => $this->calculatePlanChurnRate($period),
        ];
    }

    /**
     * Get recent system activities
     */
    private function getRecentSystemActivities(int $limit = 15): array
    {
        return DB::table('audit_logs')
            ->join('users', 'audit_logs.user_id', '=', 'users.id')
            ->select('audit_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->orderBy('audit_logs.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get admin charts data
     */
    private function getAdminCharts(string $period): array
    {
        return [
            'revenue_chart' => $this->getRevenueChartData($period),
            'user_growth_chart' => $this->getUserGrowthChartData($period),
            'plan_distribution_chart' => $this->getPlanDistributionChartData(),
            'tenant_status_chart' => $this->getTenantStatusChartData(),
        ];
    }

    /**
     * Get system alerts
     */
    private function getSystemAlerts(): array
    {
        $alerts = [];

        // Check for tenants with expired trials - Admin global precisa ver todos os tenants
        $expiredTrials = PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
            ->where('status', 'active')
            ->where('payment_method', 'trial')
            ->where('end_date', '<', Carbon::now())
            ->distinct('tenant_id')
            ->count('tenant_id');

        if ($expiredTrials > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$expiredTrials} tenants com trial expirado",
                'link' => route('admin.tenants.index', ['filter' => 'trial_expired']),
            ];
        }

        // Check for low disk space
        $diskUsage = $this->getDiskUsagePercentage();
        if ($diskUsage > 80) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "Uso de disco alto: {$diskUsage}%",
                'link' => route('admin.system.health'),
            ];
        }

        // Check for failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$failedJobs} jobs falhados",
                'link' => route('admin.queues.failed'),
            ];
        }

        return $alerts;
    }

    /**
     * Helper methods
     */
    private function getPeriodStartDate(string $period): Carbon
    {
        return match ($period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'quarter' => Carbon::now()->subQuarter(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth(),
        };
    }

    private function getSystemUptime(): string
    {
        // This would typically come from system monitoring
        return '99.9%';
    }

    private function getDatabaseSize(): string
    {
        // Detectar o tipo de banco e usar a função apropriada
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL
            $size = (int) (DB::select('SELECT pg_database_size(current_database()) as size')[0]->size ?? 0);
        } elseif ($driver === 'mysql') {
            // MySQL/MariaDB
            $databaseName = DB::getDatabaseName();
            $result = DB::select('
                SELECT SUM(data_length + index_length) as size
                FROM information_schema.tables
                WHERE table_schema = ?
            ', [$databaseName]);
            $size = (int) ($result[0]->size ?? 0);
        } else {
            // Para outros bancos, retornar 0
            $size = 0;
        }

        return $this->formatBytes($size);
    }

    private function getTotalStorageUsed(): string
    {
        // Calculate total storage used by tenants
        $totalSize = 0;

        // Implementation would depend on storage system
        return $this->formatBytes($totalSize);
    }

    private function calculateProjectedRevenue(): float
    {
        // Admin global precisa ver dados de todos os tenants
        $activeSubscriptions = PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
            ->where('status', 'active')->get();
        $projected = 0;

        foreach ($activeSubscriptions as $subscription) {
            $projected += $subscription->transaction_amount;
        }

        return $projected;
    }

    private function calculateRevenueGrowth(string $period): float
    {
        $startDate = $this->getPeriodStartDate($period);
        $previousPeriodStart = $this->getPeriodStartDate($period)->subMonth();
        $previousPeriodEnd = $startDate->copy()->subDay();

        // Admin global precisa ver dados de todos os tenants
        $currentRevenue = PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
            ->where('status', 'active')
            ->where('created_at', '>=', $startDate)
            ->sum('transaction_amount');

        $previousRevenue = PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
            ->where('status', 'active')
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->sum('transaction_amount');

        if ($previousRevenue == 0) {
            return 0;
        }

        return (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
    }

    private function calculateProviderRetention(string $period): float
    {
        $startDate = $this->getPeriodStartDate($period);

        // Como providers não têm campo status, consideramos todos como ativos
        $providersAtStart = Provider::where('created_at', '<', $startDate)->count();
        $providersStillActive = Provider::where('created_at', '<', $startDate)->count();

        if ($providersAtStart == 0) {
            return 100;
        }

        return ($providersStillActive / $providersAtStart) * 100;
    }

    private function calculateGrowthRate(string $model, string $period): float
    {
        $startDate = $this->getPeriodStartDate($period);
        $previousPeriodStart = $this->getPeriodStartDate($period)->subMonth();

        $currentCount = $model::where('created_at', '>=', $startDate)->count();
        $previousCount = $model::whereBetween('created_at', [$previousPeriodStart, $startDate])->count();

        if ($previousCount == 0) {
            return 0;
        }

        return (($currentCount - $previousCount) / $previousCount) * 100;
    }

    private function calculatePlanChurnRate(string $period): float
    {
        $startDate = $this->getPeriodStartDate($period);

        // Admin global precisa ver dados de todos os tenants
        $subscriptionsAtStart = PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
            ->where('created_at', '<', $startDate)->count();
        $cancelledSubscriptions = PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
            ->where('status', 'cancelled')
            ->where('updated_at', '>=', $startDate)
            ->count();

        if ($subscriptionsAtStart == 0) {
            return 0;
        }

        return ($cancelledSubscriptions / $subscriptionsAtStart) * 100;
    }

    private function getRevenueChartData(string $period): array
    {
        $startDate = $this->getPeriodStartDate($period);
        $data = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= Carbon::now()) {
            // Admin global precisa ver dados de todos os tenants
            $revenue = PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
                ->where('status', 'active')
                ->whereDate('created_at', $currentDate->toDateString())
                ->sum('transaction_amount');

            $data[] = [
                'date' => $currentDate->toDateString(),
                'revenue' => $revenue,
            ];

            $currentDate->addDay();
        }

        return $data;
    }

    private function getUserGrowthChartData(string $period): array
    {
        $startDate = $this->getPeriodStartDate($period);
        $data = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= Carbon::now()) {
            $customers = Customer::whereDate('created_at', '<=', $currentDate->toDateString())->count();
            $providers = Provider::whereDate('created_at', '<=', $currentDate->toDateString())->count();

            $data[] = [
                'date' => $currentDate->toDateString(),
                'customers' => $customers,
                'providers' => $providers,
            ];

            $currentDate->addDay();
        }

        return $data;
    }

    private function getPlanDistributionChartData(): array
    {
        return Plan::withCount(['planSubscriptions' => function ($query) {
            $query->where('status', 'active');
        }])->get()->map(function ($plan) {
            return [
                'name' => $plan->name,
                'value' => $plan->plan_subscriptions_count,
            ];
        })->toArray();
    }

    private function getTrialTenantsCount(): int
    {
        // Contar tenants que têm subscriptions em trial - Admin global precisa ver todos os tenants
        return PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
            ->where('status', 'active')
            ->where('payment_method', 'trial')
            ->where('end_date', '>=', now())
            ->distinct('tenant_id')
            ->count('tenant_id');
    }

    private function getTenantStatusChartData(): array
    {
        return [
            ['name' => 'Active', 'value' => Tenant::where('is_active', true)->count()],
            ['name' => 'Trial', 'value' => $this->getTrialTenantsCount()],
            ['name' => 'Inactive', 'value' => Tenant::where('is_active', false)->count()],
        ];
    }

    private function getDiskUsagePercentage(): int
    {
        // Implementation would depend on server setup
        return 45; // Placeholder
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
