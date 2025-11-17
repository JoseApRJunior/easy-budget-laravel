<?php

namespace App\Services\Application;

use App\Models\User;
use App\Models\Budget;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIAnalyticsService
{
    private $user;
    private $tenantId;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->tenantId = $user->tenant_id;
    }

    /**
     * Obter dashboard completo de analytics
     */
    public function getAnalyticsDashboard(): array
    {
        return [
            'overview' => $this->getBusinessOverview(),
            'trends' => $this->getBusinessTrends(),
            'predictions' => $this->getPredictions(),
            'suggestions' => $this->getBusinessSuggestions(),
            'performance' => $this->getPerformanceMetrics(),
            'customer_insights' => $this->getCustomerInsights(),
            'financial_health' => $this->getFinancialHealth(),
            'operational_efficiency' => $this->getOperationalEfficiency()
        ];
    }

    /**
     * Visão geral do negócio
     */
    public function getBusinessOverview(): array
    {
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // Métricas do mês atual
        $currentRevenue = $this->getMonthlyRevenue($currentMonth);
        $currentBudgets = $this->getMonthlyBudgets($currentMonth);
        $currentCustomers = $this->getActiveCustomers($currentMonth);

        // Métricas do mês passado
        $lastRevenue = $this->getMonthlyRevenue($lastMonth);
        $lastBudgets = $this->getMonthlyBudgets($lastMonth);
        $lastCustomers = $this->getActiveCustomers($lastMonth);

        // Cálculo de variações
        $revenueGrowth = $lastRevenue > 0 ? (($currentRevenue - $lastRevenue) / $lastRevenue) * 100 : 0;
        $budgetGrowth = $lastBudgets > 0 ? (($currentBudgets - $lastBudgets) / $lastBudgets) * 100 : 0;
        $customerGrowth = $lastCustomers > 0 ? (($currentCustomers - $lastCustomers) / $lastCustomers) * 100 : 0;

        return [
            'current_month' => [
                'revenue' => $currentRevenue,
                'budgets' => $currentBudgets,
                'active_customers' => $currentCustomers
            ],
            'last_month' => [
                'revenue' => $lastRevenue,
                'budgets' => $lastBudgets,
                'active_customers' => $lastCustomers
            ],
            'growth_rates' => [
                'revenue' => round($revenueGrowth, 2),
                'budgets' => round($budgetGrowth, 2),
                'customers' => round($customerGrowth, 2)
            ],
            'health_score' => $this->calculateBusinessHealthScore($currentRevenue, $currentBudgets, $currentCustomers)
        ];
    }

    /**
     * Tendências de negócio
     */
    public function getBusinessTrends(string $period = '6months'): array
    {
        $last6Months = collect(range(0, 5))->map(function ($i) {
            $date = Carbon::now()->subMonths($i);
            return [
                'month' => $date->format('M/Y'),
                'revenue' => $this->getMonthlyRevenue($date),
                'budgets' => $this->getMonthlyBudgets($date),
                'customers' => $this->getNewCustomers($date)
            ];
        })->reverse()->values();

        // Identificar tendências
        $revenueTrend = $this->identifyTrend($last6Months->pluck('revenue')->toArray());
        $budgetTrend = $this->identifyTrend($last6Months->pluck('budgets')->toArray());
        $customerTrend = $this->identifyTrend($last6Months->pluck('customers')->toArray());

        return [
            'monthly_data' => $last6Months,
            'trends' => [
                'revenue' => $revenueTrend,
                'budgets' => $budgetTrend,
                'customers' => $customerTrend
            ],
            'seasonality' => $this->detectSeasonality($last6Months)
        ];
    }

    /**
     * Previsões baseadas em IA
     */
    public function getPredictions(): array
    {
        $historicalData = $this->getHistoricalData(12); // 12 meses
        
        return [
            'next_month_revenue' => $this->predictNextMonthRevenue($historicalData),
            'next_month_budgets' => $this->predictNextMonthBudgets($historicalData),
            'churn_risk' => $this->predictChurnRisk(),
            'best_selling_services' => $this->predictBestSellers(),
            'optimal_pricing' => $this->suggestOptimalPricing()
        ];
    }

    /**
     * Sugestões de melhorias para o negócio
     */
    public function getBusinessSuggestions(): array
    {
        $suggestions = [];

        // Análise de produtos/serviços
        $lowPerformance = $this->getLowPerformanceServices();
        if (!empty($lowPerformance)) {
            $suggestions[] = [
                'type' => 'service_improvement',
                'priority' => 'high',
                'title' => 'Melhore seus serviços de baixo desempenho',
                'description' => 'Os seguintes serviços têm baixa procura: ' . implode(', ', $lowPerformance),
                'action' => 'Considere revisar preços, melhorar descrições ou oferecer pacotes promocionais',
                'potential_impact' => '+15% em vendas'
            ];
        }

        // Análise de preços
        $pricingAnalysis = $this->analyzePricing();
        if ($pricingAnalysis['underpriced']) {
            $suggestions[] = [
                'type' => 'pricing_optimization',
                'priority' => 'medium',
                'title' => 'Oportunidade de aumentar preços',
                'description' => 'Seus serviços estão 15% abaixo da média do mercado',
                'action' => 'Considere aumentar preços em 5-10% para maximizar lucros',
                'potential_impact' => '+20% em margem de lucro'
            ];
        }

        // Análise de horários
        $scheduleAnalysis = $this->analyzeScheduleEfficiency();
        if ($scheduleAnalysis['has_gaps']) {
            $suggestions[] = [
                'type' => 'schedule_optimization',
                'priority' => 'medium',
                'title' => 'Otimizar agenda de atendimento',
                'description' => 'Você tem ' . $scheduleAnalysis['empty_slots'] . ' horários vazios esta semana',
                'action' => 'Ofereça descontos para horários específicos ou crie campanhas',
                'potential_impact' => '+25% em ocupação'
            ];
        }

        // Análise de clientes
        $customerAnalysis = $this->analyzeCustomerRetention();
        if ($customerAnalysis['churn_rate'] > 20) {
            $suggestions[] = [
                'type' => 'customer_retention',
                'priority' => 'high',
                'title' => 'Melhore a retenção de clientes',
                'description' => 'Sua taxa de churn está em ' . $customerAnalysis['churn_rate'] . '%',
                'action' => 'Implemente programa de fidelidade e follow-up pós-serviço',
                'potential_impact' => '-30% em churn rate'
            ];
        }

        // Análise financeira
        $financialAnalysis = $this->analyzeFinancialHealth();
        if ($financialAnalysis['cash_flow_risk']) {
            $suggestions[] = [
                'type' => 'financial_management',
                'priority' => 'critical',
                'title' => 'Gerencie melhor seu fluxo de caixa',
                'description' => 'Você tem R$ ' . number_format($financialAnalysis['overdue_invoices'], 2, ',', '.') . ' em faturas vencidas',
                'action' => 'Intensifique cobranças e ofereça desconto para pagamento antecipado',
                'potential_impact' => '+40% em fluxo de caixa'
            ];
        }

        return $suggestions;
    }

    /**
     * Métricas de performance
     */
    public function getPerformanceMetrics(array $metrics = ['conversion_rate', 'average_ticket', 'customer_lifetime_value']): array
    {
        $currentMonth = Carbon::now();

        return [
            'conversion_rate' => $this->calculateConversionRate(),
            'average_ticket' => $this->calculateAverageTicket(),
            'customer_lifetime_value' => $this->calculateCustomerLifetimeValue(),
            'service_efficiency' => $this->calculateServiceEfficiency(),
            'response_time' => $this->calculateAverageResponseTime(),
            'satisfaction_score' => $this->estimateSatisfactionScore()
        ];
    }

    /**
     * Insights sobre clientes
     */
    public function getCustomerInsights(): array
    {
        return [
            'demographics' => $this->analyzeCustomerDemographics(),
            'behavior' => $this->analyzeCustomerBehavior(),
            'preferences' => $this->analyzeCustomerPreferences(),
            'segmentation' => $this->segmentCustomers(),
            'retention_analysis' => $this->analyzeCustomerRetention()
        ];
    }

    /**
     * Saúde financeira
     */
    public function getFinancialHealth(): array
    {
        return [
            'cash_flow' => $this->analyzeCashFlow(),
            'profitability' => $this->analyzeProfitability(),
            'financial_stability' => $this->analyzeFinancialStability(),
            'debt_analysis' => $this->analyzeDebt(),
            'investment_opportunities' => $this->identifyInvestmentOpportunities()
        ];
    }

    /**
     * Eficiência operacional
     */
    public function getOperationalEfficiency(): array
    {
        return [
            'resource_utilization' => $this->analyzeResourceUtilization(),
            'time_management' => $this->analyzeTimeManagement(),
            'cost_efficiency' => $this->analyzeCostEfficiency(),
            'quality_metrics' => $this->analyzeQualityMetrics(),
            'bottlenecks' => $this->identifyBottlenecks()
        ];
    }

    // Métodos auxiliares para cálculos específicos

    private function getMonthlyRevenue(Carbon $date): float
    {
        return Invoice::where('tenant_id', $this->tenantId)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->where('status', 'paid')
            ->sum('total') ?? 0;
    }

    private function getMonthlyBudgets(Carbon $date): int
    {
        return Budget::where('tenant_id', $this->tenantId)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->count();
    }

    private function getActiveCustomers(Carbon $date): int
    {
        return Customer::where('tenant_id', $this->tenantId)
            ->where('created_at', '<=', $date->endOfMonth())
            ->count();
    }

    private function getNewCustomers(Carbon $date): int
    {
        return Customer::where('tenant_id', $this->tenantId)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->count();
    }

    private function calculateBusinessHealthScore(float $revenue, int $budgets, int $customers): int
    {
        $score = 0;
        
        if ($revenue > 5000) $score += 25;
        if ($budgets > 10) $score += 25;
        if ($customers > 20) $score += 25;
        if ($revenue > 0 && $budgets > 0) $score += 25;
        
        return $score;
    }

    private function identifyTrend(array $data): string
    {
        if (count($data) < 2) return 'stable';
        
        $recent = array_slice($data, -3);
        $average = array_sum($recent) / count($recent);
        $last = end($recent);
        
        if ($last > $average * 1.1) return 'growing';
        if ($last < $average * 0.9) return 'declining';
        
        return 'stable';
    }

    private function detectSeasonality($data): array
    {
        // Implementar detecção de sazonalidade
        return [
            'has_seasonality' => false,
            'peak_months' => [],
            'low_months' => []
        ];
    }

    private function getHistoricalData(int $months): array
    {
        $data = [];
        for ($i = 0; $i < $months; $i++) {
            $date = Carbon::now()->subMonths($i);
            $data[] = [
                'month' => $date->format('Y-m'),
                'revenue' => $this->getMonthlyRevenue($date),
                'budgets' => $this->getMonthlyBudgets($date),
                'customers' => $this->getNewCustomers($date)
            ];
        }
        return array_reverse($data);
    }

    private function predictNextMonthRevenue(array $historicalData): array
    {
        if (empty($historicalData)) {
            return ['predicted' => 0, 'confidence' => 0, 'method' => 'none'];
        }

        // Média móvel simples
        $recentRevenues = array_slice(array_column($historicalData, 'revenue'), -3);
        $average = array_sum($recentRevenues) / count($recentRevenues);
        
        // Ajuste sazonal simples
        $trend = $this->identifyTrend(array_column($historicalData, 'revenue'));
        $adjustment = $trend === 'growing' ? 1.1 : ($trend === 'declining' ? 0.9 : 1.0);
        
        $predicted = $average * $adjustment;
        
        return [
            'predicted' => round($predicted, 2),
            'confidence' => 75,
            'method' => 'moving_average_with_trend',
            'trend' => $trend
        ];
    }

    private function predictNextMonthBudgets(array $historicalData): array
    {
        $recentBudgets = array_slice(array_column($historicalData, 'budgets'), -3);
        $average = array_sum($recentBudgets) / count($recentBudgets);
        
        return [
            'predicted' => round($average),
            'confidence' => 70,
            'method' => 'moving_average'
        ];
    }

    private function predictChurnRisk(): array
    {
        // Implementar análise de risco de churn
        return [
            'risk_level' => 'low',
            'at_risk_customers' => 0,
            'reasons' => []
        ];
    }

    private function predictBestSellers(): array
    {
        $services = Service::where('tenant_id', $this->tenantId)
            ->withCount(['budgetItems as total_sales' => function ($query) {
                $query->select(DB::raw('count(*)'));
            }])
            ->orderBy('total_sales', 'desc')
            ->take(5)
            ->get();

        return $services->map(function ($service) {
            return [
                'name' => $service->name,
                'sales' => $service->total_sales,
                'trend' => $this->identifyServiceTrend($service)
            ];
        })->toArray();
    }

    private function suggestOptimalPricing(): array
    {
        // Implementar sugestão de preços ótimos
        return [
            'services' => [],
            'market_analysis' => [],
            'recommendations' => []
        ];
    }

    private function getLowPerformanceServices(): array
    {
        return Service::where('tenant_id', $this->tenantId)
            ->withCount(['budgetItems as total_sales' => function ($query) {
                $query->whereMonth('created_at', Carbon::now()->month);
            }])
            ->having('total_sales', '<', 3)
            ->pluck('name')
            ->toArray();
    }

    private function analyzePricing(): array
    {
        // Implementar análise de preços vs mercado
        return ['underpriced' => false, 'overpriced' => false];
    }

    private function analyzeScheduleEfficiency(): array
    {
        $emptySlots = Schedule::where('tenant_id', $this->tenantId)
            ->whereDate('start_date_time', '>=', Carbon::today())
            ->whereDate('start_date_time', '<=', Carbon::today()->addWeek())
            ->whereNull('customer_id')
            ->count();

        return ['has_gaps' => $emptySlots > 10, 'empty_slots' => $emptySlots];
    }

    private function analyzeCustomerRetention(): array
    {
        $totalCustomers = Customer::where('tenant_id', $this->tenantId)->count();
        $activeCustomers = Customer::where('tenant_id', $this->tenantId)
            ->whereHas('budgets', function ($query) {
                $query->where('created_at', '>=', Carbon::now()->subMonths(6));
            })
            ->count();

        $churnRate = $totalCustomers > 0 ? (($totalCustomers - $activeCustomers) / $totalCustomers) * 100 : 0;

        return ['churn_rate' => round($churnRate, 2)];
    }

    private function analyzeFinancialHealth(): array
    {
        $overdueInvoices = Invoice::where('tenant_id', $this->tenantId)
            ->where('status', 'overdue')
            ->sum('total');

        return [
            'overdue_invoices' => $overdueInvoices,
            'cash_flow_risk' => $overdueInvoices > 1000
        ];
    }

    private function calculateConversionRate(): float
    {
        $totalBudgets = Budget::where('tenant_id', $this->tenantId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        $approvedBudgets = Budget::where('tenant_id', $this->tenantId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->where('status', 'approved')
            ->count();

        return $totalBudgets > 0 ? round(($approvedBudgets / $totalBudgets) * 100, 2) : 0;
    }

    private function calculateAverageTicket(): float
    {
        $avg = Budget::where('tenant_id', $this->tenantId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->where('status', 'approved')
            ->avg('total');

        return round($avg ?? 0, 2);
    }

    private function calculateCustomerLifetimeValue(): float
    {
        $avgRevenuePerCustomer = Invoice::where('tenant_id', $this->tenantId)
            ->where('status', 'paid')
            ->avg('total');

        return round($avgRevenuePerCustomer ?? 0, 2);
    }

    private function calculateServiceEfficiency(): float
    {
        // Implementar cálculo de eficiência de serviço
        return 85.5;
    }

    private function calculateAverageResponseTime(): int
    {
        // Implementar cálculo de tempo médio de resposta
        return 24;
    }

    private function estimateSatisfactionScore(): float
    {
        // Estimar score de satisfação baseado em métricas
        return 4.2;
    }

    private function analyzeCustomerDemographics(): array
    {
        // Implementar análise demográfica
        return ['age_groups' => [], 'locations' => [], 'gender' => []];
    }

    private function analyzeCustomerBehavior(): array
    {
        // Implementar análise de comportamento
        return ['purchase_frequency' => [], 'preferred_services' => [], 'peak_times' => []];
    }

    private function analyzeCustomerPreferences(): array
    {
        // Implementar análise de preferências
        return ['service_types' => [], 'price_ranges' => [], 'communication_channels' => []];
    }

    private function segmentCustomers(): array
    {
        // Implementar segmentação de clientes
        return ['segments' => [], 'characteristics' => []];
    }

    private function analyzeCashFlow(): array
    {
        // Implementar análise de fluxo de caixa
        return ['monthly_flow' => [], 'projections' => []];
    }

    private function analyzeProfitability(): array
    {
        // Implementar análise de rentabilidade
        return ['gross_margin' => 0, 'net_margin' => 0, 'roi' => 0];
    }

    private function analyzeFinancialStability(): array
    {
        // Implementar análise de estabilidade financeira
        return ['stability_score' => 0, 'risk_factors' => []];
    }

    private function analyzeDebt(): array
    {
        // Implementar análise de dívidas
        return ['debt_ratio' => 0, 'overdue_amount' => 0];
    }

    private function identifyInvestmentOpportunities(): array
    {
        // Identificar oportunidades de investimento
        return ['opportunities' => [], 'roi_projections' => []];
    }

    private function analyzeResourceUtilization(): array
    {
        // Analisar utilização de recursos
        return ['utilization_rate' => 0, 'peak_hours' => []];
    }

    private function analyzeTimeManagement(): array
    {
        // Analisar gestão de tempo
        return ['efficiency_score' => 0, 'time_wasters' => []];
    }

    private function analyzeCostEfficiency(): array
    {
        // Analisar eficiência de custos
        return ['cost_per_service' => 0, 'profitability_by_service' => []];
    }

    private function analyzeQualityMetrics(): array
    {
        // Analisar métricas de qualidade
        return ['quality_score' => 0, 'improvement_areas' => []];
    }

    private function identifyBottlenecks(): array
    {
        // Identificar gargalos operacionais
        return ['bottlenecks' => [], 'solutions' => []];
    }

    private function identifyServiceTrend($service): string
    {
        // Identificar tendência de serviço específico
        return 'stable';
    }
}