<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\User;
use App\Models\Provider;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Activity;
use App\Models\Profession;
use App\Models\PlanSubscription;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class AdvancedMetricsController extends Controller
{
    /**
     * Get trial tenants count.
     */
    protected function getTrialTenantsCount(): int
    {
        return PlanSubscription::where('status', 'active')
            ->where('payment_method', 'trial')
            ->where('end_date', '>=', now())
            ->distinct('tenant_id')
            ->count('tenant_id');
    }

    /**
     * Display the advanced metrics dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-advanced-metrics');

        $dateRange = $this->getDateRange($request);
        $metrics = $this->calculateMetrics($dateRange);
        $charts = $this->prepareChartData($dateRange);

        return view('admin.advanced-metrics.index', compact('metrics', 'charts', 'dateRange'));
    }

    /**
     * Get date range for metrics.
     */
    protected function getDateRange(Request $request): array
    {
        $range = $request->get('range', '30days');

        switch ($range) {
            case '7days':
                $start = Carbon::now()->subDays(7);
                $end = Carbon::now();
                break;
            case '30days':
                $start = Carbon::now()->subDays(30);
                $end = Carbon::now();
                break;
            case '90days':
                $start = Carbon::now()->subDays(90);
                $end = Carbon::now();
                break;
            case '12months':
                $start = Carbon::now()->subMonths(12);
                $end = Carbon::now();
                break;
            default:
                $start = Carbon::now()->subDays(30);
                $end = Carbon::now();
        }

        return [
            'start' => $start,
            'end' => $end,
            'range' => $range
        ];
    }

    /**
     * Calculate comprehensive metrics.
     */
    protected function calculateMetrics(array $dateRange): array
    {
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        return [
            // Tenant Metrics
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'trial_tenants' => $this->getTrialTenantsCount(),
            'suspended_tenants' => Tenant::where('is_active', false)->count(),
            'new_tenants_period' => Tenant::whereBetween('created_at', [$start, $end])->count(),

            // Plan Metrics
            'total_plans' => Plan::count(),
            'active_plans' => Plan::where('status', 'active')->count(),
            'most_popular_plan' => $this->getMostPopularPlan(),
            'plan_upgrades_period' => $this->getPlanUpgrades($start, $end),
            'plan_downgrades_period' => $this->getPlanDowngrades($start, $end),

            // User Metrics
            'total_users' => User::count(),
            'active_users_period' => User::whereBetween('last_login_at', [$start, $end])->count(),
            'new_users_period' => User::whereBetween('created_at', [$start, $end])->count(),
            'users_by_role' => $this->getUsersByRole(),

            // Provider Metrics
            'total_providers' => Provider::count(),
            'active_providers' => Provider::count(),
            'providers_by_plan' => $this->getProvidersByPlan(),
            'provider_retention_rate' => $this->calculateProviderRetention($start, $end),
            'avg_provider_customers' => Provider::withCount('customers')->avg('customers_count') ?? 0,

            // Customer Metrics
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('status', 'active')->count(),
            'new_customers_period' => Customer::whereBetween('created_at', [$start, $end])->count(),
            'customer_growth_rate' => $this->calculateCustomerGrowthRate($start, $end),

            // Revenue Metrics
            'total_revenue_period' => $this->calculateRevenue($start, $end),
            'avg_monthly_revenue' => $this->calculateAvgMonthlyRevenue($start, $end),
            'revenue_growth_rate' => $this->calculateRevenueGrowthRate($start, $end),
            'top_revenue_providers' => $this->getTopRevenueProviders($start, $end, 10),

            // Subscription Metrics
            'total_subscriptions' => PlanSubscription::count(),
            'active_subscriptions' => PlanSubscription::where('status', 'active')->count(),
            'subscription_churn_rate' => $this->calculateChurnRate($start, $end),
            'avg_subscription_value' => PlanSubscription::avg('transaction_amount') ?? 0,

            // Content Metrics
            'total_categories' => Category::count(),
            'total_activities' => Activity::count(),
            'total_professions' => Profession::count(),
            'categories_with_activities' => Category::has('activities')->count(),
            'activities_by_category' => $this->getActivitiesByCategory(),

            // System Health
            'system_health_score' => $this->calculateSystemHealthScore(),
            'critical_alerts' => $this->getCriticalAlerts(),
            'performance_score' => $this->calculatePerformanceScore(),
        ];
    }

    /**
     * Prepare chart data for visualization.
     */
    protected function prepareChartData(array $dateRange): array
    {
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        return [
            'revenue_trend' => $this->getRevenueTrend($start, $end),
            'user_growth' => $this->getUserGrowthTrend($start, $end),
            'provider_growth' => $this->getProviderGrowthTrend($start, $end),
            'subscription_trend' => $this->getSubscriptionTrend($start, $end),
            'plan_distribution' => $this->getPlanDistribution(),
            'category_activity_distribution' => $this->getCategoryActivityDistribution(),
            'revenue_by_plan' => $this->getRevenueByPlan($start, $end),
            'customer_acquisition' => $this->getCustomerAcquisitionTrend($start, $end),
            'churn_analysis' => $this->getChurnAnalysis($start, $end),
            'performance_metrics' => $this->getPerformanceMetrics($start, $end),
        ];
    }

    /**
     * Get most popular plan.
     */
    protected function getMostPopularPlan(): ?Plan
    {
        return Plan::withCount('planSubscriptions')
            ->orderBy('planSubscriptions_count', 'desc')
            ->first();
    }

    /**
     * Get plan upgrades in period.
     */
    protected function getPlanUpgrades($start, $end): int
    {
        return PlanSubscription::whereBetween('updated_at', [$start, $end])
            ->where('status', 'active')
            ->whereRaw('plan_id != (SELECT plan_id FROM plan_subscription_history WHERE plan_subscription_id = plan_subscriptions.id ORDER BY created_at DESC LIMIT 1 OFFSET 1)')
            ->count();
    }

    /**
     * Get plan downgrades in period.
     */
    protected function getPlanDowngrades($start, $end): int
    {
        return PlanSubscription::whereBetween('updated_at', [$start, $end])
            ->where('status', 'active')
            ->whereRaw('plan_id != (SELECT plan_id FROM plan_subscription_history WHERE plan_subscription_id = plan_subscriptions.id ORDER BY created_at DESC LIMIT 1 OFFSET 1)')
            ->count();
    }

    /**
     * Get users by role.
     */
    protected function getUsersByRole(): array
    {
        return User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();
    }

    /**
     * Get providers by plan.
     */
    protected function getProvidersByPlan(): array
    {
        return Provider::with('subscription.plan')
            ->get()
            ->groupBy('subscription.plan.name')
            ->map->count()
            ->toArray();
    }

    /**
     * Calculate provider retention rate.
     */
    protected function calculateProviderRetention($start, $end): float
    {
        $providersAtStart = Provider::where('created_at', '<', $start)->count();
        $providersAtEnd = Provider::where('created_at', '<', $end)->count();

        if ($providersAtStart === 0) {
            return 0;
        }

        return (($providersAtEnd / $providersAtStart) - 1) * 100;
    }

    /**
     * Calculate customer growth rate.
     */
    protected function calculateCustomerGrowthRate($start, $end): float
    {
        $customersAtStart = Customer::where('created_at', '<', $start)->count();
        $newCustomers = Customer::whereBetween('created_at', [$start, $end])->count();

        if ($customersAtStart === 0) {
            return $newCustomers > 0 ? 100 : 0;
        }

        return ($newCustomers / $customersAtStart) * 100;
    }

    /**
     * Calculate revenue for period.
     */
    protected function calculateRevenue($start, $end): float
    {
        return Invoice::whereBetween('created_at', [$start, $end])
            ->where('status', 'paid')
            ->sum('amount') ?? 0;
    }

    /**
     * Calculate average monthly revenue.
     */
    protected function calculateAvgMonthlyRevenue($start, $end): float
    {
        $months = $start->diffInMonths($end) ?: 1;
        $totalRevenue = $this->calculateRevenue($start, $end);

        return $totalRevenue / $months;
    }

    /**
     * Calculate revenue growth rate.
     */
    protected function calculateRevenueGrowthRate($start, $end): float
    {
        $previousPeriodStart = $start->copy()->subDays($start->diffInDays($end));
        $previousPeriodEnd = $start->copy();

        $currentRevenue = $this->calculateRevenue($start, $end);
        $previousRevenue = $this->calculateRevenue($previousPeriodStart, $previousPeriodEnd);

        if ($previousRevenue === 0) {
            return $currentRevenue > 0 ? 100 : 0;
        }

        return (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
    }

    /**
     * Get top revenue providers.
     */
    protected function getTopRevenueProviders($start, $end, int $limit = 10): array
    {
        return Provider::withSum(['invoices' => function($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end])
                      ->where('status', 'paid');
            }], 'amount')
            ->orderByDesc('invoices_sum_amount')
            ->limit($limit)
            ->get()
            ->map(function ($provider) {
                return [
                    'name' => $provider->name,
                    'revenue' => $provider->invoices_sum_amount ?? 0,
                    'customers_count' => $provider->customers()->count()
                ];
            })
            ->toArray();
    }

    /**
     * Calculate subscription churn rate.
     */
    protected function calculateChurnRate($start, $end): float
    {
        $subscriptionsAtStart = PlanSubscription::where('created_at', '<', $start)->count();
        $cancelledSubscriptions = PlanSubscription::whereBetween('end_date', [$start, $end])
            ->where('status', 'cancelled')
            ->count();

        if ($subscriptionsAtStart === 0) {
            return 0;
        }

        return ($cancelledSubscriptions / $subscriptionsAtStart) * 100;
    }

    /**
     * Get activities by category.
     */
    protected function getActivitiesByCategory(): array
    {
        return Category::withCount('activities')
            ->having('activities_count', '>', 0)
            ->orderByDesc('activities_count')
            ->pluck('activities_count', 'name')
            ->toArray();
    }

    /**
     * Calculate system health score.
     */
    protected function calculateSystemHealthScore(): float
    {
        $scores = [
            'tenant_health' => $this->calculateTenantHealth(),
            'provider_health' => $this->calculateProviderHealth(),
            'revenue_health' => $this->calculateRevenueHealth(),
            'subscription_health' => $this->calculateSubscriptionHealth(),
        ];

        return array_sum($scores) / count($scores);
    }

    /**
     * Get critical alerts.
     */
    protected function getCriticalAlerts(): array
    {
        $alerts = [];

        // Check for suspended tenants
        $suspendedTenants = Tenant::where('is_active', false)->count();
        if ($suspendedTenants > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$suspendedTenants} tenants suspended",
                'link' => route('admin.tenants.index', ['status' => 'suspended'])
            ];
        }

        // Check for overdue invoices
        $overdueInvoices = Invoice::where('status', 'overdue')->count();
        if ($overdueInvoices > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$overdueInvoices} overdue invoices",
                'link' => route('admin.invoices.index', ['status' => 'overdue'])
            ];
        }

        // Check for providers without subscriptions
        $providersWithoutSubscriptions = Provider::doesntHave('planSubscriptions')->count();
        if ($providersWithoutSubscriptions > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$providersWithoutSubscriptions} providers without subscriptions",
                'link' => route('admin.providers.index', ['subscription_status' => 'none'])
            ];
        }

        return $alerts;
    }

    /**
     * Calculate performance score.
     */
    protected function calculatePerformanceScore(): float
    {
        // This would integrate with performance monitoring tools
        // For now, return a placeholder based on system metrics
        $responseTime = $this->getAverageResponseTime();
        $uptime = $this->getSystemUptime();

        $score = 100;

        if ($responseTime > 1000) $score -= 20;
        elseif ($responseTime > 500) $score -= 10;

        if ($uptime < 99) $score -= 30;
        elseif ($uptime < 99.9) $score -= 15;

        return max(0, $score);
    }

    /**
     * Get revenue trend data.
     */
    protected function getRevenueTrend($start, $end): array
    {
        $data = [];
        $current = $start->copy();

        while ($current <= $end) {
            $periodStart = $current->copy();
            $periodEnd = $current->copy()->addDay();

            $revenue = Invoice::whereBetween('created_at', [$periodStart, $periodEnd])
                ->where('status', 'paid')
                ->sum('amount') ?? 0;

            $data[] = [
                'date' => $periodStart->format('Y-m-d'),
                'revenue' => $revenue
            ];

            $current->addDay();
        }

        return $data;
    }

    /**
     * Get user growth trend.
     */
    protected function getUserGrowthTrend($start, $end): array
    {
        $data = [];
        $current = $start->copy();

        while ($current <= $end) {
            $count = User::whereDate('created_at', '<=', $current)->count();

            $data[] = [
                'date' => $current->format('Y-m-d'),
                'count' => $count
            ];

            $current->addDay();
        }

        return $data;
    }

    /**
     * Get provider growth trend.
     */
    protected function getProviderGrowthTrend($start, $end): array
    {
        $data = [];
        $current = $start->copy();

        while ($current <= $end) {
            $count = Provider::whereDate('created_at', '<=', $current)->count();

            $data[] = [
                'date' => $current->format('Y-m-d'),
                'count' => $count
            ];

            $current->addDay();
        }

        return $data;
    }

    /**
     * Get subscription trend.
     */
    protected function getSubscriptionTrend($start, $end): array
    {
        $data = [];
        $current = $start->copy();

        while ($current <= $end) {
            $active = PlanSubscription::whereDate('created_at', '<=', $current)
                ->where(function($query) use ($current) {
                    $query->where('status', 'active')
                          ->orWhere(function($q) use ($current) {
                              $q->where('status', 'pending')
                                ->whereDate('end_date', '>', $current);
                          });
                })
                ->count();

            $data[] = [
                'date' => $current->format('Y-m-d'),
                'count' => $active
            ];

            $current->addDay();
        }

        return $data;
    }

    /**
     * Get plan distribution.
     */
    protected function getPlanDistribution(): array
    {
        return Plan::withCount('planSubscriptions')
            ->having('planSubscriptions_count', '>', 0)
            ->orderByDesc('planSubscriptions_count')
            ->get()
            ->map(function ($plan) {
                return [
                    'name' => $plan->name,
                    'count' => $plan->subscriptions_count,
                    'percentage' => 0 // Will be calculated in frontend
                ];
            })
            ->toArray();
    }

    /**
     * Get category activity distribution.
     */
    protected function getCategoryActivityDistribution(): array
    {
        return Category::withCount('activities')
            ->having('activities_count', '>', 0)
            ->orderByDesc('activities_count')
            ->limit(10)
            ->get()
            ->map(function ($category) {
                return [
                    'name' => $category->name,
                    'count' => $category->activities_count
                ];
            })
            ->toArray();
    }

    /**
     * Get revenue by plan.
     */
    protected function getRevenueByPlan($start, $end): array
    {
        return Plan::withSum(['invoices' => function($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end])
                      ->where('status', 'paid');
            }], 'amount')
            ->having('invoices_sum_amount', '>', 0)
            ->orderByDesc('invoices_sum_amount')
            ->get()
            ->map(function ($plan) {
                return [
                    'name' => $plan->name,
                    'revenue' => $plan->invoices_sum_amount ?? 0
                ];
            })
            ->toArray();
    }

    /**
     * Get customer acquisition trend.
     */
    protected function getCustomerAcquisitionTrend($start, $end): array
    {
        $data = [];
        $current = $start->copy();

        while ($current <= $end) {
            $newCustomers = Customer::whereDate('created_at', $current)->count();

            $data[] = [
                'date' => $current->format('Y-m-d'),
                'new_customers' => $newCustomers
            ];

            $current->addDay();
        }

        return $data;
    }

    /**
     * Get churn analysis.
     */
    protected function getChurnAnalysis($start, $end): array
    {
        $data = [];
        $current = $start->copy();

        while ($current <= $end) {
            $cancelled = PlanSubscription::whereDate('end_date', $current)
                ->where('status', 'cancelled')
                ->count();
            $active = PlanSubscription::whereDate('created_at', '<=', $current)
                ->where(function($query) use ($current) {
                    $query->where('status', 'active')
                          ->orWhere(function($q) use ($current) {
                              $q->where('status', 'pending')
                                ->whereDate('end_date', '>', $current);
                          });
                })
                ->count();

            $churnRate = $active > 0 ? ($cancelled / $active) * 100 : 0;

            $data[] = [
                'date' => $current->format('Y-m-d'),
                'cancelled' => $cancelled,
                'churn_rate' => round($churnRate, 2)
            ];

            $current->addDay();
        }

        return $data;
    }

    /**
     * Get performance metrics.
     */
    protected function getPerformanceMetrics($start, $end): array
    {
        return [
            'avg_response_time' => $this->getAverageResponseTime(),
            'uptime_percentage' => $this->getSystemUptime(),
            'error_rate' => $this->getErrorRate($start, $end),
            'throughput' => $this->getThroughput($start, $end)
        ];
    }

    /**
     * Helper methods for performance metrics (placeholders).
     */
    protected function getAverageResponseTime(): float
    {
        // This would integrate with monitoring tools
        return 250; // ms
    }

    protected function getSystemUptime(): float
    {
        // This would integrate with monitoring tools
        return 99.9;
    }

    protected function getErrorRate($start, $end): float
    {
        // This would integrate with logging systems
        return 0.1;
    }

    protected function getThroughput($start, $end): int
    {
        // This would integrate with monitoring tools
        return 1000;
    }

    /**
     * Calculate tenant health score.
     */
    protected function calculateTenantHealth(): float
    {
        $total = Tenant::count();
        $active = Tenant::where('is_active', true)->count();

        return $total > 0 ? ($active / $total) * 100 : 0;
    }

    /**
     * Calculate provider health score.
     */
    protected function calculateProviderHealth(): float
    {
        // Como providers não têm campo status, consideramos todos como ativos
        return 100;
    }

    /**
     * Calculate revenue health score.
     */
    protected function calculateRevenueHealth(): float
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $currentRevenue = Invoice::where('status', 'paid')
            ->whereMonth('created_at', $currentMonth)
            ->sum('amount') ?? 0;

        $lastRevenue = Invoice::where('status', 'paid')
            ->whereMonth('created_at', $lastMonth)
            ->sum('amount') ?? 0;

        if ($lastRevenue === 0) {
            return $currentRevenue > 0 ? 100 : 0;
        }

        $growth = (($currentRevenue - $lastRevenue) / $lastRevenue) * 100;

        return min(100, max(0, 50 + ($growth / 2)));
    }

    /**
     * Calculate subscription health score.
     */
    protected function calculateSubscriptionHealth(): float
    {
        $total = PlanSubscription::count();
        $active = PlanSubscription::where('status', 'active')->count();

        return $total > 0 ? ($active / $total) * 100 : 0;
    }

    /**
     * Get real-time metrics (AJAX endpoint).
     */
    public function realtime(Request $request): JsonResponse
    {
        $this->authorize('view-advanced-metrics');

        $metrics = [
            'active_users' => User::where('last_login_at', '>', Carbon::now()->subMinutes(15))->count(),
            'new_signups_today' => User::whereDate('created_at', Carbon::today())->count(),
            'revenue_today' => Invoice::whereDate('created_at', Carbon::today())->where('status', 'paid')->sum('amount') ?? 0,
            'active_subscriptions' => PlanSubscription::where('status', 'active')->count(),
            'system_load' => $this->getSystemLoad(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
        ];

        return response()->json($metrics);
    }

    /**
     * Get system load (placeholder).
     */
    protected function getSystemLoad(): float
    {
        // This would integrate with system monitoring
        return 0.5;
    }

    /**
     * Get memory usage (placeholder).
     */
    protected function getMemoryUsage(): float
    {
        // This would integrate with system monitoring
        return 65.0;
    }

    /**
     * Get disk usage (placeholder).
     */
    protected function getDiskUsage(): float
    {
        // This would integrate with system monitoring
        return 45.0;
    }

    /**
     * Export metrics data.
     */
    public function export(Request $request)
    {
        $this->authorize('export-metrics');

        $format = $request->get('format', 'csv');
        $dateRange = $this->getDateRange($request);
        $metrics = $this->calculateMetrics($dateRange);

        if ($format === 'json') {
            return response()->json($metrics);
        }

        // CSV export
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="metrics_' . date('Y-m-d') . '.csv"'
        ];

        return response()->stream(function() use ($metrics) {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, ['Metric', 'Value']);

            // Data
            foreach ($metrics as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                fputcsv($handle, [str_replace('_', ' ', ucfirst($key)), $value]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
