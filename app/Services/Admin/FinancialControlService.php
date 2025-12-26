<?php

namespace App\Services\Admin;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialControlService
{
    public function getFinancialOverview(): array
    {
        try {
            $totalRevenue = $this->calculateTotalRevenue();
            $totalCosts = $this->calculateTotalCosts();
            $activeProviders = $this->getActiveProvidersCount();
            $monthlyGrowth = $this->calculateMonthlyGrowth();

            return [
                'total_revenue' => $totalRevenue,
                'total_costs' => $totalCosts,
                'net_profit' => $totalRevenue - $totalCosts,
                'profit_margin' => $totalRevenue > 0 ? (($totalRevenue - $totalCosts) / $totalRevenue) * 100 : 0,
                'active_providers' => $activeProviders,
                'monthly_growth' => $monthlyGrowth,
                'avg_revenue_per_provider' => $activeProviders > 0 ? $totalRevenue / $activeProviders : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting financial overview: '.$e->getMessage());

            return $this->getDefaultFinancialData();
        }
    }

    public function getProviderFinancialDetails(int $tenantId): array
    {
        try {
            $tenant = Tenant::with(['provider', 'planSubscriptions.plan'])->find($tenantId);

            if (! $tenant) {
                return $this->getDefaultProviderFinancialData();
            }

            $revenue = $this->calculateProviderRevenue($tenantId);
            $costs = $this->calculateProviderCosts($tenantId);
            $subscriptionCost = $this->getProviderSubscriptionCost($tenant);
            $paymentProcessingFees = $this->calculatePaymentProcessingFees($tenantId);

            return [
                'provider_name' => $tenant->name,
                'tenant_id' => $tenantId,
                'revenue' => [
                    'total' => $revenue['total'],
                    'this_month' => $revenue['this_month'],
                    'last_month' => $revenue['last_month'],
                    'growth_rate' => $this->calculateGrowthRate($revenue['this_month'], $revenue['last_month']),
                ],
                'costs' => [
                    'total' => $costs,
                    'subscription' => $subscriptionCost,
                    'payment_fees' => $paymentProcessingFees,
                    'operational' => $costs - $subscriptionCost - $paymentProcessingFees,
                ],
                'profitability' => [
                    'net_profit' => $revenue['total'] - $costs,
                    'profit_margin' => $revenue['total'] > 0 ? (($revenue['total'] - $costs) / $revenue['total']) * 100 : 0,
                ],
                'metrics' => [
                    'avg_ticket' => $this->calculateAverageTicket($tenantId),
                    'customer_lifetime_value' => $this->calculateCustomerLifetimeValue($tenantId),
                    'invoice_payment_rate' => $this->calculateInvoicePaymentRate($tenantId),
                ],
                'alerts' => $this->getFinancialAlerts($tenant),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting provider financial details for tenant '.$tenantId.': '.$e->getMessage());

            return $this->getDefaultProviderFinancialData();
        }
    }

    public function getFinancialReports(array $filters = []): array
    {
        try {
            $startDate = $filters['start_date'] ?? Carbon::now()->startOfMonth();
            $endDate = $filters['end_date'] ?? Carbon::now()->endOfMonth();
            $tenantId = $filters['tenant_id'] ?? null;

            return [
                'revenue_by_period' => $this->getRevenueByPeriod($startDate, $endDate, $tenantId),
                'costs_by_category' => $this->getCostsByCategory($startDate, $endDate, $tenantId),
                'provider_performance' => $this->getProviderPerformance($startDate, $endDate),
                'payment_method_analysis' => $this->getPaymentMethodAnalysis($startDate, $endDate, $tenantId),
                'outstanding_receivables' => $this->getOutstandingReceivables($tenantId),
                'financial_trends' => $this->getFinancialTrends($startDate, $endDate, $tenantId),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting financial reports: '.$e->getMessage());

            return $this->getDefaultReportsData();
        }
    }

    public function getBudgetAlerts(): array
    {
        try {
            $alerts = [];
            $tenants = Tenant::with(['planSubscriptions.plan'])->get();

            foreach ($tenants as $tenant) {
                $currentSpending = $this->calculateProviderCosts($tenant->id);
                $budgetLimit = $this->getProviderBudgetLimit($tenant);

                if ($budgetLimit > 0 && $currentSpending > ($budgetLimit * 0.8)) {
                    $alerts[] = [
                        'type' => 'budget_warning',
                        'severity' => $currentSpending > $budgetLimit ? 'critical' : 'warning',
                        'tenant_id' => $tenant->id,
                        'provider_name' => $tenant->name,
                        'current_spending' => $currentSpending,
                        'budget_limit' => $budgetLimit,
                        'percentage_used' => ($currentSpending / $budgetLimit) * 100,
                        'message' => "Provider {$tenant->name} has used ".round(($currentSpending / $budgetLimit) * 100).'% of their budget',
                    ];
                }
            }

            return $alerts;
        } catch (\Exception $e) {
            Log::error('Error getting budget alerts: '.$e->getMessage());

            return [];
        }
    }

    private function calculateTotalRevenue(): float
    {
        return Payment::where('status', 'approved')->sum('amount') ?? 0;
    }

    private function calculateTotalCosts(): float
    {
        $subscriptionCosts = PlanSubscription::where('status', 'active')
            ->join('plans', 'plan_subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price') ?? 0;

        $paymentProcessingCosts = Payment::where('status', 'approved')
            ->sum(DB::raw('amount * 0.029')) ?? 0; // 2.9% processing fee

        return $subscriptionCosts + $paymentProcessingCosts;
    }

    private function getActiveProvidersCount(): int
    {
        return Tenant::where('status', 'active')->count() ?? 0;
    }

    private function calculateMonthlyGrowth(): float
    {
        $currentMonth = Carbon::now()->month;
        $previousMonth = Carbon::now()->subMonth()->month;
        $currentYear = Carbon::now()->year;
        $previousYear = Carbon::now()->subMonth()->year;

        $currentMonthRevenue = Payment::where('status', 'approved')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('amount') ?? 0;

        $previousMonthRevenue = Payment::where('status', 'approved')
            ->whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $previousYear)
            ->sum('amount') ?? 0;

        return $this->calculateGrowthRate($currentMonthRevenue, $previousMonthRevenue);
    }

    private function calculateProviderRevenue(int $tenantId): array
    {
        $total = Payment::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->sum('amount') ?? 0;

        $thisMonth = Payment::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount') ?? 0;

        $lastMonth = Payment::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->sum('amount') ?? 0;

        return [
            'total' => $total,
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
        ];
    }

    private function calculateProviderCosts(int $tenantId): float
    {
        $subscriptionCost = $this->getProviderSubscriptionCost(Tenant::find($tenantId));
        $paymentProcessingFees = $this->calculatePaymentProcessingFees($tenantId);

        return $subscriptionCost + $paymentProcessingFees;
    }

    private function getProviderSubscriptionCost(Tenant $tenant): float
    {
        $subscription = $tenant->planSubscriptions->first();
        if ($subscription && $subscription->plan) {
            return $subscription->plan->price ?? 0;
        }

        return 0;
    }

    private function calculatePaymentProcessingFees(int $tenantId): float
    {
        return Payment::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->sum(DB::raw('amount * 0.029')) ?? 0; // 2.9% processing fee
    }

    private function calculateAverageTicket(int $tenantId): float
    {
        $invoiceCount = Invoice::where('tenant_id', $tenantId)->count();
        $totalRevenue = Payment::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->sum('amount') ?? 0;

        return $invoiceCount > 0 ? $totalRevenue / $invoiceCount : 0;
    }

    private function calculateCustomerLifetimeValue(int $tenantId): float
    {
        $customerCount = Customer::where('tenant_id', $tenantId)->count();
        $totalRevenue = Payment::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->sum('amount') ?? 0;

        return $customerCount > 0 ? $totalRevenue / $customerCount : 0;
    }

    private function calculateInvoicePaymentRate(int $tenantId): float
    {
        $totalInvoices = Invoice::where('tenant_id', $tenantId)->count();
        $paidInvoices = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->count();

        return $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0;
    }

    private function calculateGrowthRate(float $current, float $previous): float
    {
        return $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
    }

    private function getFinancialAlerts(Tenant $tenant): array
    {
        $alerts = [];

        // Check for overdue invoices
        $overdueInvoices = Invoice::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('due_date', '<', Carbon::now())
            ->count();

        if ($overdueInvoices > 0) {
            $alerts[] = [
                'type' => 'overdue_invoices',
                'severity' => 'warning',
                'message' => "Você tem {$overdueInvoices} faturas vencidas",
            ];
        }

        // Check for low payment rate
        $paymentRate = $this->calculateInvoicePaymentRate($tenant->id);
        if ($paymentRate < 70) {
            $alerts[] = [
                'type' => 'low_payment_rate',
                'severity' => 'warning',
                'message' => 'Sua taxa de pagamento está abaixo de 70%',
            ];
        }

        return $alerts;
    }

    private function getProviderBudgetLimit(Tenant $tenant): float
    {
        // Implement budget limit logic based on plan or custom settings
        $subscription = $tenant->planSubscriptions->first();
        return $subscription && $subscription->plan
            ? $subscription->plan->max_monthly_spend ?? 1000
            : 1000;
    }

    private function getRevenueByPeriod(Carbon $startDate, Carbon $endDate, ?int $tenantId = null): array
    {
        $query = Payment::where('status', 'approved')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(amount) as total')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getCostsByCategory(Carbon $startDate, Carbon $endDate, ?int $tenantId = null): array
    {
        $categories = [
            'subscription' => 0,
            'payment_processing' => 0,
            'operational' => 0,
        ];

        // Subscription costs
        $subscriptionQuery = PlanSubscription::where('status', 'active')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->join('plans', 'plan_subscriptions.plan_id', '=', 'plans.id');

        if ($tenantId) {
            $subscriptionQuery->where('tenant_id', $tenantId);
        }

        $categories['subscription'] = $subscriptionQuery->sum('plans.price') ?? 0;

        // Payment processing fees
        $paymentQuery = Payment::where('status', 'approved')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($tenantId) {
            $paymentQuery->where('tenant_id', $tenantId);
        }

        $categories['payment_processing'] = $paymentQuery->sum(DB::raw('amount * 0.029')) ?? 0;

        return $categories;
    }

    private function getProviderPerformance(Carbon $startDate, Carbon $endDate): array
    {
        return Tenant::with(['provider'])
            ->select(
                'tenants.id',
                'tenants.name',
                DB::raw('(SELECT SUM(amount) FROM payments WHERE payments.tenant_id = tenants.id AND payments.status = "approved" AND payments.created_at BETWEEN ? AND ?) as revenue'),
                DB::raw('(SELECT COUNT(*) FROM customers WHERE customers.tenant_id = tenants.id) as customer_count'),
                DB::raw('(SELECT COUNT(*) FROM invoices WHERE invoices.tenant_id = tenants.id AND invoices.status = "paid") as paid_invoices')
            )
            ->setBindings([$startDate, $endDate])
            ->orderBy('revenue', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getPaymentMethodAnalysis(Carbon $startDate, Carbon $endDate, ?int $tenantId = null): array
    {
        $query = Payment::where('status', 'approved')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->select(
            'payment_method',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total')
        )
            ->groupBy('payment_method')
            ->get()
            ->toArray();
    }

    private function getOutstandingReceivables(?int $tenantId = null): array
    {
        $query = Invoice::where('status', 'pending')
            ->where('due_date', '<', Carbon::now());

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->select(
            'tenant_id',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total_amount) as total')
        )
            ->groupBy('tenant_id')
            ->get()
            ->toArray();
    }

    private function getFinancialTrends(Carbon $startDate, Carbon $endDate, ?int $tenantId = null): array
    {
        $months = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $revenueQuery = Payment::where('status', 'approved')
                ->whereBetween('created_at', [$monthStart, $monthEnd]);

            $costsQuery = PlanSubscription::where('status', 'active')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->join('plans', 'plan_subscriptions.plan_id', '=', 'plans.id');

            if ($tenantId) {
                $revenueQuery->where('tenant_id', $tenantId);
                $costsQuery->where('tenant_id', $tenantId);
            }

            $months[] = [
                'month' => $current->format('Y-m'),
                'revenue' => $revenueQuery->sum('amount') ?? 0,
                'costs' => $costsQuery->sum('plans.price') ?? 0,
            ];

            $current->addMonth();
        }

        return $months;
    }

    private function getDefaultFinancialData(): array
    {
        return [
            'total_revenue' => 0,
            'total_costs' => 0,
            'net_profit' => 0,
            'profit_margin' => 0,
            'active_providers' => 0,
            'monthly_growth' => 0,
            'avg_revenue_per_provider' => 0,
        ];
    }

    private function getDefaultProviderFinancialData(): array
    {
        return [
            'provider_name' => 'N/A',
            'tenant_id' => 0,
            'revenue' => [
                'total' => 0,
                'this_month' => 0,
                'last_month' => 0,
                'growth_rate' => 0,
            ],
            'costs' => [
                'total' => 0,
                'subscription' => 0,
                'payment_fees' => 0,
                'operational' => 0,
            ],
            'profitability' => [
                'net_profit' => 0,
                'profit_margin' => 0,
            ],
            'metrics' => [
                'avg_ticket' => 0,
                'customer_lifetime_value' => 0,
                'invoice_payment_rate' => 0,
            ],
            'alerts' => [],
        ];
    }

    private function getDefaultReportsData(): array
    {
        return [
            'revenue_by_period' => [],
            'costs_by_category' => [],
            'provider_performance' => [],
            'payment_method_analysis' => [],
            'outstanding_receivables' => [],
            'financial_trends' => [],
        ];
    }
}
