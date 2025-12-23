<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Customer;
use App\Services\Application\AIAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AIAnalyticsController extends Controller
{
    protected $aiAnalyticsService;

    public function __construct(AIAnalyticsService $aiAnalyticsService)
    {
        $this->aiAnalyticsService = $aiAnalyticsService;
    }

    public function index(Request $request)
    {
        $analytics = $this->aiAnalyticsService->getBusinessOverview();

        return view('pages.analytics.index', compact('analytics'));
    }

    public function overview(Request $request)
    {
        $overview = $this->aiAnalyticsService->getBusinessOverview();
        $performance = $this->aiAnalyticsService->getPerformanceMetrics();
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $newCustomersMonth = Customer::where('tenant_id', $tenantId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        return response()->json([
            'revenue' => [
                'total' => (float) ($overview['current_month']['revenue'] ?? 0),
                'growth' => (float) ($overview['growth_rates']['revenue'] ?? 0),
            ],
            'conversion' => [
                'rate' => (float) ($performance['conversion_rate'] ?? 0),
                'trend' => 'stable',
            ],
            'customers' => [
                'active' => (int) ($overview['current_month']['active_customers'] ?? 0),
                'new_this_month' => (int) $newCustomersMonth,
            ],
            'ticket' => [
                'average' => (float) ($performance['average_ticket'] ?? 0),
                'growth' => (float) ($overview['growth_rates']['budgets'] ?? 0),
            ],
        ]);
    }

    public function trends(Request $request)
    {
        $period = $request->get('period', '6months');
        $trends = $this->aiAnalyticsService->getBusinessTrends($period);
        $labels = collect($trends['monthly_data'] ?? [])->pluck('month')->all();
        $values = collect($trends['monthly_data'] ?? [])->pluck('revenue')->all();

        return response()->json([
            'labels' => $labels,
            'values' => $values,
        ]);
    }

    public function predictions(Request $request)
    {
        $predictions = $this->aiAnalyticsService->getPredictions();

        return response()->json($predictions);
    }

    public function suggestions(Request $request)
    {
        $suggestions = $this->aiAnalyticsService->getBusinessSuggestions();

        return response()->json(['suggestions' => $suggestions]);
    }

    public function performance(Request $request)
    {
        $metrics = $request->get('metrics', ['conversion_rate', 'average_ticket', 'customer_lifetime_value']);
        $performance = $this->aiAnalyticsService->getPerformanceMetrics($metrics);

        return response()->json($performance);
    }

    public function customers(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $totalCustomers = Customer::where('tenant_id', $tenantId)->count();
        $activeCustomers = Customer::where('tenant_id', $tenantId)
            ->whereHas('budgets', function ($query) {
                $query->where('created_at', '>=', Carbon::now()->subMonths(6));
            })
            ->count();
        $newCustomersMonth = Customer::where('tenant_id', $tenantId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        $churnRate = $totalCustomers > 0 ? round((($totalCustomers - $activeCustomers) / $totalCustomers) * 100, 2) : 0;

        return response()->json([
            'total_customers' => (int) $totalCustomers,
            'active_customers' => (int) $activeCustomers,
            'new_customers_month' => (int) $newCustomersMonth,
            'churn_rate' => (float) $churnRate,
            'main_segment' => 'Análise em progresso...',
        ]);
    }

    public function financial(Request $request)
    {
        $overview = $this->aiAnalyticsService->getBusinessOverview();
        $monthlyRevenue = (float) ($overview['current_month']['revenue'] ?? 0);
        $monthlyExpenses = 0.0;
        $profitMargin = 0.0;
        $cashFlow = $monthlyRevenue - $monthlyExpenses;
        $healthScore = (int) ($overview['health_score'] ?? 0);

        return response()->json([
            'monthly_revenue' => $monthlyRevenue,
            'monthly_expenses' => $monthlyExpenses,
            'profit_margin' => $profitMargin,
            'cash_flow' => $cashFlow,
            'health_score' => $healthScore,
        ]);
    }

    public function efficiency(Request $request)
    {
        $efficiency = $this->aiAnalyticsService->getOperationalEfficiency();

        return response()->json($efficiency);
    }
}
