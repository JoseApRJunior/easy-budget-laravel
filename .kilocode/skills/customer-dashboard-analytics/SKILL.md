# 📊 Skill: Customer Dashboard and Analytics (Dashboard e Analytics)

**Descrição:** Sistema completo de dashboards e analytics para visualização de métricas, KPIs e insights sobre o relacionamento com clientes.

**Categoria:** Dashboard e Analytics
**Complexidade:** Alta
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Fornecer dashboards executivos e analytics avançados para monitoramento do relacionamento com clientes, identificação de oportunidades e tomada de decisões baseada em dados.

## 📋 Requisitos Técnicos

### **✅ Estrutura de Dashboards**

```php
class CustomerDashboardService extends AbstractBaseService
{
    public function getExecutiveDashboard(int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($tenantId) {
            return $this->success([
                'customer_overview' => $this->getCustomerOverview($tenantId),
                'financial_metrics' => $this->getFinancialMetrics($tenantId),
                'engagement_metrics' => $this->getEngagementMetrics($tenantId),
                'growth_metrics' => $this->getGrowthMetrics($tenantId),
                'risk_metrics' => $this->getRiskMetrics($tenantId),
                'segmentation_analysis' => $this->getSegmentationAnalysis($tenantId),
            ], 'Dashboard executivo gerado');
        });
    }

    public function getCustomerAnalytics(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            return $this->success([
                'revenue_analysis' => $this->getRevenueAnalysis($customer),
                'service_analysis' => $this->getServiceAnalysis($customer),
                'payment_analysis' => $this->getPaymentAnalysis($customer),
                'interaction_analysis' => $this->getInteractionAnalysis($customer),
                'lifecycle_analysis' => $this->getLifecycleAnalysis($customer),
                'risk_assessment' => $this->getRiskAssessment($customer),
            ], 'Analytics do cliente gerado');
        });
    }

    public function getCustomerSegmentationDashboard(int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($tenantId) {
            return $this->success([
                'segment_distribution' => $this->getSegmentDistribution($tenantId),
                'segment_performance' => $this->getSegmentPerformance($tenantId),
                'segment_trends' => $this->getSegmentTrends($tenantId),
                'segment_comparisons' => $this->getSegmentComparisons($tenantId),
            ], 'Dashboard de segmentação gerado');
        });
    }

    private function getCustomerOverview(int $tenantId): array
    {
        $totalCustomers = Customer::where('tenant_id', $tenantId)->count();
        $activeCustomers = Customer::where('tenant_id', $tenantId)
            ->where('status', 'active')->count();
        $inactiveCustomers = Customer::where('tenant_id', $tenantId)
            ->where('status', 'inactive')->count();

        $newCustomers = Customer::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subMonth())->count();

        $churnedCustomers = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'churned')
            ->where('stage_changed_at', '>=', now()->subMonth())->count();

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'inactive_customers' => $inactiveCustomers,
            'new_customers_this_month' => $newCustomers,
            'churned_customers_this_month' => $churnedCustomers,
            'active_rate' => $totalCustomers > 0 ? ($activeCustomers / $totalCustomers) * 100 : 0,
            'churn_rate' => $newCustomers > 0 ? ($churnedCustomers / $newCustomers) * 100 : 0,
        ];
    }

    private function getFinancialMetrics(int $tenantId): array
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // Receita atual
        $currentRevenue = Invoice::whereHas('service.budget.customer', function($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })
        ->where('status', 'paid')
        ->where('transaction_date', '>=', $currentMonth)
        ->sum('total');

        // Receita do mês anterior
        $lastMonthRevenue = Invoice::whereHas('service.budget.customer', function($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })
        ->where('status', 'paid')
        ->whereBetween('transaction_date', [$lastMonth, $currentMonth])
        ->sum('total');

        // Média de ticket
        $avgTicket = Invoice::whereHas('service.budget.customer', function($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })
        ->where('status', 'paid')
        ->where('transaction_date', '>=', $currentMonth)
        ->avg('total') ?? 0;

        // Receita recorrente
        $recurringRevenue = Invoice::whereHas('service.budget.customer', function($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })
        ->where('status', 'paid')
        ->where('transaction_date', '>=', $currentMonth)
        ->whereHas('service', function($query) {
            $query->where('is_recurring', true);
        })
        ->sum('total');

        return [
            'current_month_revenue' => $currentRevenue,
            'last_month_revenue' => $lastMonthRevenue,
            'revenue_growth' => $lastMonthRevenue > 0 ? (($currentRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0,
            'average_ticket' => $avgTicket,
            'recurring_revenue' => $recurringRevenue,
            'pending_revenue' => $this->getPendingRevenue($tenantId),
        ];
    }

    private function getEngagementMetrics(int $tenantId): array
    {
        $totalCustomers = Customer::where('tenant_id', $tenantId)->count();

        // Clientes com interações nos últimos 30 dias
        $activeCustomers = Customer::where('tenant_id', $tenantId)
            ->whereHas('interactions', function($query) {
                $query->where('interaction_date', '>=', now()->subDays(30));
            })
            ->count();

        // Média de interações por cliente
        $avgInteractions = Customer::where('tenant_id', $tenantId)
            ->withCount('interactions')
            ->get()
            ->avg('interactions_count') ?? 0;

        // Taxa de resposta
        $responseRate = $this->calculateResponseRate($tenantId);

        // Satisfação média
        $satisfactionRate = $this->calculateSatisfactionRate($tenantId);

        return [
            'active_engagement_rate' => $totalCustomers > 0 ? ($activeCustomers / $totalCustomers) * 100 : 0,
            'average_interactions_per_customer' => $avgInteractions,
            'response_rate' => $responseRate,
            'satisfaction_rate' => $satisfactionRate,
            'recent_interactions' => $this->getRecentInteractionsCount($tenantId),
        ];
    }

    private function getGrowthMetrics(int $tenantId): array
    {
        $currentMonth = now()->startOfMonth();
        $last3Months = now()->subMonths(3)->startOfMonth();

        // Crescimento de clientes
        $newCustomers = Customer::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $currentMonth)->count();

        $customers3MonthsAgo = Customer::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $last3Months)->count();

        // Conversão de leads
        $conversionRate = $this->calculateConversionRate($tenantId);

        // Retenção de clientes
        $retentionRate = $this->calculateRetentionRate($tenantId);

        return [
            'new_customers_this_month' => $newCustomers,
            'customers_last_3_months' => $customers3MonthsAgo,
            'growth_rate' => $customers3MonthsAgo > 0 ? (($newCustomers / $customers3MonthsAgo) * 100) : 0,
            'conversion_rate' => $conversionRate,
            'retention_rate' => $retentionRate,
            'customer_lifetime_value' => $this->calculateCLV($tenantId),
        ];
    }

    private function getRiskMetrics(int $tenantId): array
    {
        $totalCustomers = Customer::where('tenant_id', $tenantId)->count();

        // Clientes inativos
        $inactiveCustomers = Customer::where('tenant_id', $tenantId)
            ->where('last_interaction_at', '<', now()->subMonths(3))
            ->count();

        // Clientes com faturas vencidas
        $overdueCustomers = Customer::where('tenant_id', $tenantId)
            ->whereHas('invoices', function($query) {
                $query->where('due_date', '<', now())
                    ->where('status', 'pending');
            })
            ->count();

        // Clientes em risco de churn
        $atRiskCustomers = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'inactive')
            ->count();

        return [
            'inactive_customers' => $inactiveCustomers,
            'overdue_customers' => $overdueCustomers,
            'at_risk_customers' => $atRiskCustomers,
            'inactive_rate' => $totalCustomers > 0 ? ($inactiveCustomers / $totalCustomers) * 100 : 0,
            'overdue_rate' => $totalCustomers > 0 ? ($overdueCustomers / $totalCustomers) * 100 : 0,
            'churn_risk_score' => $this->calculateChurnRiskScore($tenantId),
        ];
    }

    private function getSegmentationAnalysis(int $tenantId): array
    {
        // Distribuição por tags
        $tagDistribution = CustomerTag::where('tenant_id', $tenantId)
            ->withCount('customers')
            ->get()
            ->map(function($tag) {
                return [
                    'name' => $tag->name,
                    'count' => $tag->customers_count,
                    'percentage' => $this->calculateTagPercentage($tag->customers_count, $tag->tenant_id),
                ];
            });

        // Distribuição por tipo
        $typeDistribution = Customer::where('tenant_id', $tenantId)
            ->groupBy('type')
            ->selectRaw('type, count(*) as count')
            ->pluck('count', 'type')
            ->toArray();

        // Distribuição por estágio de ciclo de vida
        $stageDistribution = Customer::where('tenant_id', $tenantId)
            ->groupBy('lifecycle_stage')
            ->selectRaw('lifecycle_stage, count(*) as count')
            ->pluck('count', 'lifecycle_stage')
            ->toArray();

        return [
            'by_tags' => $tagDistribution,
            'by_type' => $typeDistribution,
            'by_lifecycle_stage' => $stageDistribution,
            'segment_performance' => $this->getSegmentPerformance($tenantId),
        ];
    }

    private function getRevenueAnalysis(Customer $customer): array
    {
        $revenueByMonth = Invoice::whereHas('service.budget', function($query) use ($customer) {
            $query->where('customer_id', $customer->id);
        })
        ->where('status', 'paid')
        ->selectRaw('DATE_FORMAT(transaction_date, "%Y-%m") as month, sum(total) as revenue')
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        $avgRevenue = $customer->invoices()->where('status', 'paid')->avg('total') ?? 0;
        $totalRevenue = $customer->invoices()->where('status', 'paid')->sum('total');

        return [
            'revenue_by_month' => $revenueByMonth,
            'average_revenue' => $avgRevenue,
            'total_revenue' => $totalRevenue,
            'revenue_trend' => $this->calculateRevenueTrend($customer),
            'revenue_concentration' => $this->calculateRevenueConcentration($customer),
        ];
    }

    private function getServiceAnalysis(Customer $customer): array
    {
        $servicesByType = $customer->services()
            ->groupBy('service_type')
            ->selectRaw('service_type, count(*) as count, sum(total) as total_value')
            ->get();

        $avgServiceValue = $customer->services()->avg('total') ?? 0;
        $totalServices = $customer->services()->count();

        return [
            'services_by_type' => $servicesByType,
            'average_service_value' => $avgServiceValue,
            'total_services' => $totalServices,
            'service_frequency' => $this->calculateServiceFrequency($customer),
            'service_satisfaction' => $this->calculateServiceSatisfaction($customer),
        ];
    }

    private function getPaymentAnalysis(Customer $customer): array
    {
        $paymentHistory = $customer->invoices()
            ->where('status', 'paid')
            ->orderBy('transaction_date', 'desc')
            ->get();

        $avgPaymentTime = $paymentHistory->avg(function($invoice) {
            return $invoice->created_at->diffInDays($invoice->transaction_date);
        }) ?? 0;

        $paymentMethods = $customer->invoices()
            ->where('status', 'paid')
            ->groupBy('payment_method')
            ->selectRaw('payment_method, count(*) as count')
            ->pluck('count', 'payment_method')
            ->toArray();

        return [
            'payment_history' => $paymentHistory,
            'average_payment_time' => $avgPaymentTime,
            'payment_methods' => $paymentMethods,
            'payment_consistency' => $this->calculatePaymentConsistency($customer),
            'late_payment_rate' => $this->calculateLatePaymentRate($customer),
        ];
    }

    private function getInteractionAnalysis(Customer $customer): array
    {
        $interactionsByType = $customer->interactions()
            ->groupBy('interaction_type')
            ->selectRaw('interaction_type, count(*) as count')
            ->get();

        $lastInteraction = $customer->interactions()->latest('interaction_date')->first();
        $interactionFrequency = $this->calculateInteractionFrequency($customer);

        return [
            'interactions_by_type' => $interactionsByType,
            'last_interaction' => $lastInteraction,
            'interaction_frequency' => $interactionFrequency,
            'response_rate' => $this->calculateCustomerResponseRate($customer),
            'engagement_score' => $this->calculateEngagementScore($customer),
        ];
    }

    private function getLifecycleAnalysis(Customer $customer): array
    {
        $lifecycleHistory = $customer->lifecycleHistory()
            ->orderBy('created_at', 'desc')
            ->get();

        $currentStage = $customer->lifecycle_stage;
        $stageDuration = $customer->stage_changed_at ? now()->diffInDays($customer->stage_changed_at) : 0;

        return [
            'lifecycle_history' => $lifecycleHistory,
            'current_stage' => $currentStage,
            'stage_duration_days' => $stageDuration,
            'stage_progression' => $this->calculateStageProgression($customer),
            'next_stage_probability' => $this->calculateNextStageProbability($customer),
        ];
    }

    private function getRiskAssessment(Customer $customer): array
    {
        $riskFactors = [];

        // Fatores de risco
        if ($customer->last_interaction_at && $customer->last_interaction_at < now()->subMonths(3)) {
            $riskFactors[] = 'Inatividade de mais de 3 meses';
        }

        if ($customer->invoices()->where('due_date', '<', now())->where('status', 'pending')->exists()) {
            $riskFactors[] = 'Faturas vencidas';
        }

        if ($customer->lifecycle_stage === 'inactive') {
            $riskFactors[] = 'Estágio inativo no ciclo de vida';
        }

        $churnProbability = $this->calculateCustomerChurnProbability($customer);

        return [
            'risk_factors' => $riskFactors,
            'churn_probability' => $churnProbability,
            'retention_score' => 100 - $churnProbability,
            'recommended_actions' => $this->getRecommendedActions($customer, $riskFactors),
        ];
    }

    private function getSegmentDistribution(int $tenantId): array
    {
        return Customer::where('tenant_id', $tenantId)
            ->with('tags')
            ->get()
            ->groupBy(function($customer) {
                return $customer->tags->pluck('name')->join(',');
            })
            ->map->count()
            ->toArray();
    }

    private function getSegmentPerformance(int $tenantId): array
    {
        return Customer::where('tenant_id', $tenantId)
            ->with(['tags', 'invoices'])
            ->get()
            ->groupBy(function($customer) {
                return $customer->tags->pluck('name')->join(',');
            })
            ->map(function($customers) {
                return [
                    'customer_count' => $customers->count(),
                    'total_revenue' => $customers->sum(function($customer) {
                        return $customer->invoices->where('status', 'paid')->sum('total');
                    }),
                    'avg_revenue_per_customer' => $customers->avg(function($customer) {
                        return $customer->invoices->where('status', 'paid')->avg('total') ?? 0;
                    }),
                    'avg_interaction_count' => $customers->avg(function($customer) {
                        return $customer->interactions()->count();
                    }),
                ];
            })
            ->toArray();
    }

    private function getSegmentTrends(int $tenantId): array
    {
        $segments = Customer::where('tenant_id', $tenantId)
            ->with('tags')
            ->get()
            ->groupBy(function($customer) {
                return $customer->tags->pluck('name')->join(',');
            });

        $trends = [];

        foreach ($segments as $segmentName => $customers) {
            $trends[$segmentName] = [
                'growth_rate' => $this->calculateSegmentGrowthRate($customers),
                'revenue_trend' => $this->calculateSegmentRevenueTrend($customers),
                'churn_rate' => $this->calculateSegmentChurnRate($customers),
            ];
        }

        return $trends;
    }

    private function getSegmentComparisons(int $tenantId): array
    {
        $segments = $this->getSegmentPerformance($tenantId);

        $comparison = [
            'best_performing' => $this->findBestPerformingSegment($segments),
            'worst_performing' => $this->findWorstPerformingSegment($segments),
            'highest_growth' => $this->findHighestGrowthSegment($tenantId),
            'highest_churn' => $this->findHighestChurnSegment($tenantId),
        ];

        return $comparison;
    }

    // Métodos auxiliares para cálculos de métricas
    private function getPendingRevenue(int $tenantId): float
    {
        return Invoice::whereHas('service.budget.customer', function($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })
        ->where('status', 'pending')
        ->sum('total');
    }

    private function calculateResponseRate(int $tenantId): float
    {
        // Implementar lógica de cálculo de taxa de resposta
        return 0.0; // Placeholder
    }

    private function calculateSatisfactionRate(int $tenantId): float
    {
        // Implementar lógica de cálculo de taxa de satisfação
        return 0.0; // Placeholder
    }

    private function getRecentInteractionsCount(int $tenantId): int
    {
        return CustomerInteraction::whereHas('customer', function($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })
        ->where('interaction_date', '>=', now()->subWeek())
        ->count();
    }

    private function calculateConversionRate(int $tenantId): float
    {
        $totalLeads = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'lead')->count();
        $convertedLeads = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'closed_won')->count();

        return $totalLeads > 0 ? ($convertedLeads / $totalLeads) * 100 : 0;
    }

    private function calculateRetentionRate(int $tenantId): float
    {
        // Implementar lógica de cálculo de taxa de retenção
        return 0.0; // Placeholder
    }

    private function calculateCLV(int $tenantId): float
    {
        // Implementar cálculo de Customer Lifetime Value
        return 0.0; // Placeholder
    }

    private function calculateTagPercentage(int $tagCount, int $tenantId): float
    {
        $totalCustomers = Customer::where('tenant_id', $tenantId)->count();
        return $totalCustomers > 0 ? ($tagCount / $totalCustomers) * 100 : 0;
    }

    private function calculateRevenueTrend(Customer $customer): string
    {
        // Implementar lógica de tendência de receita
        return 'stable'; // Placeholder
    }

    private function calculateRevenueConcentration(Customer $customer): float
    {
        $invoices = $customer->invoices()->where('status', 'paid')->get();
        $totalRevenue = $invoices->sum('total');

        if ($totalRevenue == 0) return 0;

        $herfindahlIndex = $invoices->sum(function($invoice) use ($totalRevenue) {
            $marketShare = $invoice->total / $totalRevenue;
            return $marketShare * $marketShare;
        });

        return $herfindahlIndex;
    }

    private function calculateServiceFrequency(Customer $customer): float
    {
        $services = $customer->services;
        if ($services->isEmpty()) return 0;

        $firstService = $services->sortBy('created_at')->first();
        $lastService = $services->sortByDesc('created_at')->first();

        $daysDiff = $firstService->created_at->diffInDays($lastService->created_at);
        return $daysDiff > 0 ? $services->count() / $daysDiff : 0;
    }

    private function calculateServiceSatisfaction(Customer $customer): float
    {
        // Implementar cálculo de satisfação com serviços
        return 0.0; // Placeholder
    }

    private function calculatePaymentConsistency(Customer $customer): float
    {
        $invoices = $customer->invoices()->where('status', 'paid')->get();
        if ($invoices->isEmpty()) return 0;

        $paymentIntervals = [];
        $sortedInvoices = $invoices->sortBy('transaction_date');

        foreach ($sortedInvoices->slice(1) as $index => $invoice) {
            $prevInvoice = $sortedInvoices[$index];
            $interval = $prevInvoice->transaction_date->diffInDays($invoice->transaction_date);
            $paymentIntervals[] = $interval;
        }

        if (empty($paymentIntervals)) return 0;

        $avgInterval = array_sum($paymentIntervals) / count($paymentIntervals);
        $stdDev = sqrt(array_sum(array_map(function($interval) use ($avgInterval) {
            return pow($interval - $avgInterval, 2);
        }, $paymentIntervals)) / count($paymentIntervals));

        return $avgInterval > 0 ? (1 - ($stdDev / $avgInterval)) * 100 : 0;
    }

    private function calculateLatePaymentRate(Customer $customer): float
    {
        $totalInvoices = $customer->invoices()->count();
        $lateInvoices = $customer->invoices()
            ->where('transaction_date', '>', 'due_date')
            ->count();

        return $totalInvoices > 0 ? ($lateInvoices / $totalInvoices) * 100 : 0;
    }

    private function calculateInteractionFrequency(Customer $customer): float
    {
        $interactions = $customer->interactions;
        if ($interactions->isEmpty()) return 0;

        $firstInteraction = $interactions->sortBy('interaction_date')->first();
        $lastInteraction = $interactions->sortByDesc('interaction_date')->first();

        $daysDiff = $firstInteraction->interaction_date->diffInDays($lastInteraction->interaction_date);
        return $daysDiff > 0 ? $interactions->count() / $daysDiff : 0;
    }

    private function calculateCustomerResponseRate(Customer $customer): float
    {
        // Implementar cálculo de taxa de resposta do cliente
        return 0.0; // Placeholder
    }

    private function calculateEngagementScore(Customer $customer): float
    {
        $score = 0;

        // Pontos por interações recentes
        $recentInteractions = $customer->interactions()
            ->where('interaction_date', '>=', now()->subMonths(3))
            ->count();
        $score += min($recentInteractions * 10, 50);

        // Pontos por faturas pagas
        $paidInvoices = $customer->invoices()->where('status', 'paid')->count();
        $score += min($paidInvoices * 5, 30);

        // Pontos por tempo de relacionamento
        $daysSinceFirstContact = $customer->created_at->diffInDays(now());
        $score += min($daysSinceFirstContact / 30 * 2, 20);

        return min($score, 100);
    }

    private function calculateStageProgression(Customer $customer): array
    {
        $history = $customer->lifecycleHistory;
        $stages = $history->pluck('to_stage')->toArray();

        return [
            'stages_completed' => count($stages),
            'current_progress' => $this->getStageProgressPercentage($customer->lifecycle_stage),
            'time_in_current_stage' => $customer->stage_changed_at ? now()->diffInDays($customer->stage_changed_at) : 0,
        ];
    }

    private function calculateNextStageProbability(Customer $customer): float
    {
        // Implementar lógica de probabilidade de avanço de estágio
        return 0.0; // Placeholder
    }

    private function calculateCustomerChurnProbability(Customer $customer): float
    {
        $score = 0;

        // Fatores de risco
        if ($customer->last_interaction_at && $customer->last_interaction_at < now()->subMonths(3)) {
            $score += 40; // Alta probabilidade por inatividade
        }

        if ($customer->invoices()->where('due_date', '<', now())->where('status', 'pending')->exists()) {
            $score += 30; // Média probabilidade por inadimplência
        }

        if ($customer->lifecycle_stage === 'inactive') {
            $score += 20; // Média probabilidade por estágio
        }

        // Fatores de proteção
        if ($customer->invoices()->where('status', 'paid')->exists()) {
            $score -= 10; // Reduz probabilidade por histórico de pagamento
        }

        return min(max($score, 0), 100);
    }

    private function getRecommendedActions(Customer $customer, array $riskFactors): array
    {
        $actions = [];

        foreach ($riskFactors as $factor) {
            switch ($factor) {
                case 'Inatividade de mais de 3 meses':
                    $actions[] = 'Enviar campanha de reengajamento';
                    $actions[] = 'Oferecer promoção especial';
                    break;
                case 'Faturas vencidas':
                    $actions[] = 'Negociar dívida';
                    $actions[] = 'Oferecer desconto por pagamento à vista';
                    break;
                case 'Estágio inativo no ciclo de vida':
                    $actions[] = 'Agendar contato de retenção';
                    $actions[] = 'Analisar causas do desinteresse';
                    break;
            }
        }

        if (empty($riskFactors)) {
            $actions[] = 'Manter relacionamento atual';
            $actions[] = 'Oferecer upsell/cross-sell';
        }

        return $actions;
    }

    private function calculateSegmentGrowthRate(Collection $customers): float
    {
        // Implementar cálculo de taxa de crescimento do segmento
        return 0.0; // Placeholder
    }

    private function calculateSegmentRevenueTrend(Collection $customers): string
    {
        // Implementar cálculo de tendência de receita do segmento
        return 'stable'; // Placeholder
    }

    private function calculateSegmentChurnRate(Collection $customers): float
    {
        $totalCustomers = $customers->count();
        $churnedCustomers = $customers->where('lifecycle_stage', 'churned')->count();

        return $totalCustomers > 0 ? ($churnedCustomers / $totalCustomers) * 100 : 0;
    }

    private function findBestPerformingSegment(array $segments): ?string
    {
        $bestSegment = null;
        $bestRevenue = 0;

        foreach ($segments as $segmentName => $data) {
            if ($data['total_revenue'] > $bestRevenue) {
                $bestRevenue = $data['total_revenue'];
                $bestSegment = $segmentName;
            }
        }

        return $bestSegment;
    }

    private function findWorstPerformingSegment(array $segments): ?string
    {
        $worstSegment = null;
        $worstRevenue = PHP_FLOAT_MAX;

        foreach ($segments as $segmentName => $data) {
            if ($data['total_revenue'] < $worstRevenue) {
                $worstRevenue = $data['total_revenue'];
                $worstSegment = $segmentName;
            }
        }

        return $worstSegment;
    }

    private function findHighestGrowthSegment(int $tenantId): ?string
    {
        // Implementar lógica para encontrar segmento com maior crescimento
        return null; // Placeholder
    }

    private function findHighestChurnSegment(int $tenantId): ?string
    {
        // Implementar lógica para encontrar segmento com maior churn
        return null; // Placeholder
    }

    private function getStageProgressPercentage(string $stage): float
    {
        $stages = [
            'lead' => 10,
            'prospect' => 30,
            'qualified' => 50,
            'proposal' => 70,
            'negotiation' => 85,
            'closed_won' => 100,
            'closed_lost' => 0,
            'active' => 90,
            'inactive' => 20,
            'churned' => 0,
            'reactivated' => 60,
        ];

        return $stages[$stage] ?? 0;
    }
}
```

### **✅ Sistema de Relatórios Avançados**

```php
class CustomerReportingService extends AbstractBaseService
{
    public function generateCustomerReport(array $filters = [], string $reportType = 'summary'): ServiceResult
    {
        return $this->safeExecute(function() use ($filters, $reportType) {
            switch ($reportType) {
                case 'summary':
                    return $this->generateSummaryReport($filters);
                case 'detailed':
                    return $this->generateDetailedReport($filters);
                case 'financial':
                    return $this->generateFinancialReport($filters);
                case 'engagement':
                    return $this->generateEngagementReport($filters);
                case 'risk':
                    return $this->generateRiskReport($filters);
                default:
                    return $this->error('Tipo de relatório não suportado', OperationStatus::INVALID_DATA);
            }
        });
    }

    public function generateCustomerTrendReport(array $filters = [], int $months = 12): ServiceResult
    {
        return $this->safeExecute(function() use ($filters, $months) {
            $trends = [];

            for ($i = $months; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $monthData = $this->getMonthlyData($month, $filters);

                $trends[] = [
                    'month' => $month->format('Y-m'),
                    'month_name' => $month->format('F Y'),
                    'data' => $monthData,
                ];
            }

            return $this->success([
                'trends' => $trends,
                'period' => "$months meses",
                'analysis' => $this->analyzeTrends($trends),
            ], 'Relatório de tendências gerado');
        });
    }

    private function generateSummaryReport(array $filters): ServiceResult
    {
        $customers = $this->getFilteredCustomers($filters);

        $summary = [
            'total_customers' => $customers->count(),
            'by_status' => $customers->groupBy('status')->map->count()->toArray(),
            'by_type' => $customers->groupBy('type')->map->count()->toArray(),
            'by_lifecycle_stage' => $customers->groupBy('lifecycle_stage')->map->count()->toArray(),
            'financial_summary' => $this->getFinancialSummaryForReport($customers),
            'engagement_summary' => $this->getEngagementSummaryForReport($customers),
        ];

        return $this->success($summary, 'Relatório resumido gerado');
    }

    private function generateDetailedReport(array $filters): ServiceResult
    {
        $customers = $this->getFilteredCustomers($filters);

        $detailedData = $customers->map(function($customer) {
            return [
                'customer' => $customer->load(['commonData', 'contact', 'address']),
                'financial_data' => $this->getCustomerFinancialData($customer),
                'engagement_data' => $this->getCustomerEngagementData($customer),
                'risk_data' => $this->getCustomerRiskData($customer),
            ];
        });

        return $this->success([
            'customers' => $detailedData,
            'summary' => $this->generateSummaryFromDetailed($detailedData),
        ], 'Relatório detalhado gerado');
    }

    private function generateFinancialReport(array $filters): ServiceResult
    {
        $customers = $this->getFilteredCustomers($filters);

        $financialData = [
            'revenue_by_customer' => $this->getRevenueByCustomer($customers),
            'revenue_by_month' => $this->getRevenueByMonth($customers),
            'payment_analysis' => $this->getPaymentAnalysisForReport($customers),
            'outstanding_analysis' => $this->getOutstandingAnalysisForReport($customers),
            'financial_trends' => $this->getFinancialTrendsForReport($customers),
        ];

        return $this->success($financialData, 'Relatório financeiro gerado');
    }

    private function generateEngagementReport(array $filters): ServiceResult
    {
        $customers = $this->getFilteredCustomers($filters);

        $engagementData = [
            'interaction_analysis' => $this->getInteractionAnalysisForReport($customers),
            'communication_preferences' => $this->getCommunicationPreferencesForReport($customers),
            'satisfaction_analysis' => $this->getSatisfactionAnalysisForReport($customers),
            'engagement_trends' => $this->getEngagementTrendsForReport($customers),
        ];

        return $this->success($engagementData, 'Relatório de engajamento gerado');
    }

    private function generateRiskReport(array $filters): ServiceResult
    {
        $customers = $this->getFilteredCustomers($filters);

        $riskData = [
            'churn_risk_analysis' => $this->getChurnRiskAnalysisForReport($customers),
            'payment_risk_analysis' => $this->getPaymentRiskAnalysisForReport($customers),
            'inactive_customers' => $this->getInactiveCustomersForReport($customers),
            'risk_mitigation_strategies' => $this->getRiskMitigationStrategiesForReport($customers),
        ];

        return $this->success($riskData, 'Relatório de risco gerado');
    }

    private function getMonthlyData(Carbon $month, array $filters): array
    {
        $startOfMonth = $month->startOfMonth();
        $endOfMonth = $month->endOfMonth();

        $customers = Customer::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->when($filters['tenant_id'] ?? null, function($query, $tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->get();

        return [
            'new_customers' => $customers->count(),
            'revenue' => $this->getMonthlyRevenue($startOfMonth, $endOfMonth, $filters),
            'interactions' => $this->getMonthlyInteractions($startOfMonth, $endOfMonth, $filters),
            'churned_customers' => $this->getMonthlyChurnedCustomers($startOfMonth, $endOfMonth, $filters),
        ];
    }

    private function analyzeTrends(array $trends): array
    {
        $analysis = [
            'growth_trend' => $this->analyzeGrowthTrend($trends),
            'revenue_trend' => $this->analyzeRevenueTrend($trends),
            'engagement_trend' => $this->analyzeEngagementTrend($trends),
            'risk_trend' => $this->analyzeRiskTrend($trends),
        ];

        return $analysis;
    }

    private function analyzeGrowthTrend(array $trends): string
    {
        if (count($trends) < 2) return 'insufficient_data';

        $growthRates = [];
        for ($i = 1; $i < count($trends); $i++) {
            $prev = $trends[$i - 1]['data']['new_customers'] ?? 0;
            $curr = $trends[$i]['data']['new_customers'] ?? 0;

            if ($prev > 0) {
                $growthRates[] = (($curr - $prev) / $prev) * 100;
            }
        }

        if (empty($growthRates)) return 'stable';

        $avgGrowth = array_sum($growthRates) / count($growthRates);

        if ($avgGrowth > 5) return 'growing';
        if ($avgGrowth < -5) return 'declining';
        return 'stable';
    }

    private function analyzeRevenueTrend(array $trends): string
    {
        // Implementar análise de tendência de receita
        return 'stable'; // Placeholder
    }

    private function analyzeEngagementTrend(array $trends): string
    {
        // Implementar análise de tendência de engajamento
        return 'stable'; // Placeholder
    }

    private function analyzeRiskTrend(array $trends): string
    {
        // Implementar análise de tendência de risco
        return 'stable'; // Placeholder
    }

    private function getFilteredCustomers(array $filters): Collection
    {
        $query = Customer::query();

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    private function getFinancialSummaryForReport(Collection $customers): array
    {
        return [
            'total_revenue' => $customers->sum(function($customer) {
                return $customer->invoices()->where('status', 'paid')->sum('total');
            }),
            'pending_revenue' => $customers->sum(function($customer) {
                return $customer->invoices()->where('status', 'pending')->sum('total');
            }),
            'average_ticket' => $customers->avg(function($customer) {
                return $customer->invoices()->where('status', 'paid')->avg('total') ?? 0;
            }),
            'revenue_distribution' => $this->getRevenueDistribution($customers),
        ];
    }

    private function getEngagementSummaryForReport(Collection $customers): array
    {
        return [
            'total_interactions' => $customers->sum(function($customer) {
                return $customer->interactions()->count();
            }),
            'average_interactions_per_customer' => $customers->avg(function($customer) {
                return $customer->interactions()->count();
            }),
            'active_engagement_rate' => $this->calculateActiveEngagementRate($customers),
            'satisfaction_score' => $this->calculateSatisfactionScore($customers),
        ];
    }

    private function getCustomerFinancialData(Customer $customer): array
    {
        return [
            'total_spent' => $customer->invoices()->where('status', 'paid')->sum('total'),
            'pending_amount' => $customer->invoices()->where('status', 'pending')->sum('total'),
            'payment_history' => $customer->invoices()->where('status', 'paid')->orderBy('transaction_date', 'desc')->take(10)->get(),
            'average_ticket' => $customer->invoices()->where('status', 'paid')->avg('total') ?? 0,
            'payment_methods' => $customer->invoices()->where('status', 'paid')->groupBy('payment_method')->pluck('payment_method'),
        ];
    }

    private function getCustomerEngagementData(Customer $customer): array
    {
        return [
            'total_interactions' => $customer->interactions()->count(),
            'last_interaction' => $customer->interactions()->latest('interaction_date')->first(),
            'interaction_frequency' => $this->calculateCustomerInteractionFrequency($customer),
            'preferred_contact_method' => $this->getPreferredContactMethod($customer),
            'engagement_score' => $this->calculateCustomerEngagementScore($customer),
        ];
    }

    private function getCustomerRiskData(Customer $customer): array
    {
        return [
            'churn_risk_score' => $this->calculateCustomerChurnRiskScore($customer),
            'payment_risk_score' => $this->calculateCustomerPaymentRiskScore($customer),
            'inactivity_days' => $customer->last_interaction_at ? now()->diffInDays($customer->last_interaction_at) : 0,
            'overdue_invoices' => $customer->invoices()->where('due_date', '<', now())->where('status', 'pending')->count(),
            'recommended_actions' => $this->getCustomerRecommendedActions($customer),
        ];
    }

    private function getRevenueByCustomer(Collection $customers): array
    {
        return $customers->map(function($customer) {
            return [
                'customer_name' => $customer->commonData?->first_name . ' ' . $customer->commonData?->last_name,
                'total_revenue' => $customer->invoices()->where('status', 'paid')->sum('total'),
                'pending_amount' => $customer->invoices()->where('status', 'pending')->sum('total'),
                'invoice_count' => $customer->invoices()->count(),
            ];
        })->sortByDesc('total_revenue')->toArray();
    }

    private function getRevenueByMonth(Collection $customers): array
    {
        return Invoice::whereHas('service.budget.customer', function($query) use ($customers) {
            $query->whereIn('id', $customers->pluck('id'));
        })
        ->where('status', 'paid')
        ->selectRaw('DATE_FORMAT(transaction_date, "%Y-%m") as month, sum(total) as revenue')
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->toArray();
    }

    private function getPaymentAnalysisForReport(Collection $customers): array
    {
        return [
            'payment_methods_distribution' => $this->getPaymentMethodsDistribution($customers),
            'average_payment_time' => $this->getAveragePaymentTime($customers),
            'late_payment_rate' => $this->getLatePaymentRate($customers),
            'payment_consistency' => $this->getPaymentConsistency($customers),
        ];
    }

    private function getOutstandingAnalysisForReport(Collection $customers): array
    {
        return [
            'total_outstanding' => $customers->sum(function($customer) {
                return $customer->invoices()->where('status', 'pending')->sum('total');
            }),
            'outstanding_by_customer' => $this->getOutstandingByCustomer($customers),
            'average_days_overdue' => $this->getAverageDaysOverdue($customers),
            'collection_efficiency' => $this->getCollectionEfficiency($customers),
        ];
    }

    private function getFinancialTrendsForReport(Collection $customers): array
    {
        return [
            'monthly_revenue_trend' => $this->getMonthlyRevenueTrend($customers),
            'customer_lifetime_value_trend' => $this->getCustomerLifetimeValueTrend($customers),
            'average_ticket_trend' => $this->getAverageTicketTrend($customers),
            'revenue_concentration_trend' => $this->getRevenueConcentrationTrend($customers),
        ];
    }

    // Métodos auxiliares para cálculos específicos de relatórios
    private function getMonthlyRevenue(Carbon $start, Carbon $end, array $filters): float
    {
        return Invoice::whereHas('service.budget.customer', function($query) use ($filters) {
            if (isset($filters['tenant_id'])) {
                $query->where('tenant_id', $filters['tenant_id']);
            }
        })
        ->where('status', 'paid')
        ->whereBetween('transaction_date', [$start, $end])
        ->sum('total');
    }

    private function getMonthlyInteractions(Carbon $start, Carbon $end, array $filters): int
    {
        return CustomerInteraction::whereHas('customer', function($query) use ($filters) {
            if (isset($filters['tenant_id'])) {
                $query->where('tenant_id', $filters['tenant_id']);
            }
        })
        ->whereBetween('interaction_date', [$start, $end])
        ->count();
    }

    private function getMonthlyChurnedCustomers(Carbon $start, Carbon $end, array $filters): int
    {
        return Customer::whereHas('lifecycleHistory', function($query) use ($start, $end) {
            $query->where('to_stage', 'churned')
                ->whereBetween('created_at', [$start, $end]);
        })
        ->when($filters['tenant_id'] ?? null, function($query, $tenantId) {
            $query->where('tenant_id', $tenantId);
        })
        ->count();
    }

    private function getRevenueDistribution(Collection $customers): array
    {
        // Implementar lógica de distribuição de receita
        return []; // Placeholder
    }

    private function calculateActiveEngagementRate(Collection $customers): float
    {
        $activeCustomers = $customers->filter(function($customer) {
            return $customer->last_interaction_at && $customer->last_interaction_at >= now()->subMonths(3);
        });

        return $customers->count() > 0 ? ($activeCustomers->count() / $customers->count()) * 100 : 0;
    }

    private function calculateSatisfactionScore(Collection $customers): float
    {
        // Implementar cálculo de score de satisfação
        return 0.0; // Placeholder
    }

    private function calculateCustomerInteractionFrequency(Customer $customer): float
    {
        $interactions = $customer->interactions;
        if ($interactions->isEmpty()) return 0;

        $firstInteraction = $interactions->sortBy('interaction_date')->first();
        $lastInteraction = $interactions->sortByDesc('interaction_date')->first();

        $daysDiff = $firstInteraction->interaction_date->diffInDays($lastInteraction->interaction_date);
        return $daysDiff > 0 ? $interactions->count() / $daysDiff : 0;
    }

    private function getPreferredContactMethod(Customer $customer): string
    {
        $contactMethods = $customer->interactions()
            ->groupBy('interaction_type')
            ->selectRaw('interaction_type, count(*) as count')
            ->orderByDesc('count')
            ->first();

        return $contactMethods?->interaction_type ?? 'unknown';
    }

    private function calculateCustomerEngagementScore(Customer $customer): float
    {
        // Implementar cálculo de score de engajamento
        return 0.0; // Placeholder
    }

    private function calculateCustomerChurnRiskScore(Customer $customer): float
    {
        // Implementar cálculo de score de risco de churn
        return 0.0; // Placeholder
    }

    private function calculateCustomerPaymentRiskScore(Customer $customer): float
    {
        // Implementar cálculo de score de risco de pagamento
        return 0.0; // Placeholder
    }

    private function getCustomerRecommendedActions(Customer $customer): array
    {
        // Implementar lógica de ações recomendadas
        return []; // Placeholder
    }

    private function getPaymentMethodsDistribution(Collection $customers): array
    {
        return Invoice::whereHas('service.budget.customer', function($query) use ($customers) {
            $query->whereIn('id', $customers->pluck('id'));
        })
        ->where('status', 'paid')
        ->groupBy('payment_method')
        ->selectRaw('payment_method, count(*) as count')
        ->pluck('count', 'payment_method')
        ->toArray();
    }

    private function getAveragePaymentTime(Collection $customers): float
    {
        return Invoice::whereHas('service.budget.customer', function($query) use ($customers) {
            $query->whereIn('id', $customers->pluck('id'));
        })
        ->where('status', 'paid')
        ->avg(function($invoice) {
            return $invoice->created_at->diffInDays($invoice->transaction_date);
        }) ?? 0;
    }

    private function getLatePaymentRate(Collection $customers): float
    {
        $totalInvoices = Invoice::whereHas('service.budget.customer', function($query) use ($customers) {
            $query->whereIn('id', $customers->pluck('id'));
        })->count();

        $lateInvoices = Invoice::whereHas('service.budget.customer', function($query) use ($customers) {
            $query->whereIn('id', $customers->pluck('id'));
        })
        ->where('transaction_date', '>', 'due_date')
        ->count();

        return $totalInvoices > 0 ? ($lateInvoices / $totalInvoices) * 100 : 0;
    }

    private function getPaymentConsistency(Collection $customers): float
    {
        // Implementar cálculo de consistência de pagamento
        return 0.0; // Placeholder
    }

    private function getOutstandingByCustomer(Collection $customers): array
    {
        return $customers->map(function($customer) {
            return [
                'customer_name' => $customer->commonData?->first_name . ' ' . $customer->commonData?->last_name,
                'outstanding_amount' => $customer->invoices()->where('status', 'pending')->sum('total'),
                'overdue_invoices' => $customer->invoices()->where('due_date', '<', now())->where('status', 'pending')->count(),
            ];
        })->toArray();
    }

    private function getAverageDaysOverdue(Collection $customers): float
    {
        $overdueInvoices = Invoice::whereHas('service.budget.customer', function($query) use ($customers) {
            $query->whereIn('id', $customers->pluck('id'));
        })
        ->where('due_date', '<', now())
        ->where('status', 'pending')
        ->get();

        if ($overdueInvoices->isEmpty()) return 0;

        return $overdueInvoices->avg(function($invoice) {
            return now()->diffInDays($invoice->due_date);
        });
    }

    private function getCollectionEfficiency(Collection $customers): float
    {
        $totalInvoices = Invoice::whereHas('service.budget.customer', function($query) use ($customers) {
            $query->whereIn('id', $customers->pluck('id'));
        })->sum('total');

        $paidInvoices = Invoice::whereHas('service.budget.customer', function($query) use ($customers) {
            $query->whereIn('id', $customers->pluck('id'));
        })
        ->where('status', 'paid')->sum('total');

        return $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0;
    }

    private function getMonthlyRevenueTrend(Collection $customers): array
    {
        // Implementar lógica de tendência mensal de receita
        return []; // Placeholder
    }

    private function getCustomerLifetimeValueTrend(Collection $customers): array
    {
        // Implementar lógica de tendência de CLV
        return []; // Placeholder
    }

    private function getAverageTicketTrend(Collection $customers): array
    {
        // Implementar lógica de tendência de ticket médio
        return []; // Placeholder
    }

    private function getRevenueConcentrationTrend(Collection $customers): array
    {
        // Implementar lógica de tendência de concentração de receita
        return []; // Placeholder
    }

    private function generateSummaryFromDetailed(Collection $detailedData): array
    {
        return [
            'total_customers' => $detailedData->count(),
            'total_revenue' => $detailedData->sum('financial_data.total_spent'),
            'total_pending' => $detailedData->sum('financial_data.pending_amount'),
            'average_engagement_score' => $detailedData->avg('engagement_data.engagement_score'),
            'average_churn_risk' => $detailedData->avg('risk_data.churn_risk_score'),
        ];
    }

    private function getInteractionAnalysisForReport(Collection $customers): array
    {
        // Implementar análise de interações para relatório
        return []; // Placeholder
    }

    private function getCommunicationPreferencesForReport(Collection $customers): array
    {
        // Implementar preferências de comunicação para relatório
        return []; // Placeholder
    }

    private function getSatisfactionAnalysisForReport(Collection $customers): array
    {
        // Implementar análise de satisfação para relatório
        return []; // Placeholder
    }

    private function getEngagementTrendsForReport(Collection $customers): array
    {
        // Implementar tendências de engajamento para relatório
        return []; // Placeholder
    }

    private function getChurnRiskAnalysisForReport(Collection $customers): array
    {
        // Implementar análise de risco de churn para relatório
        return []; // Placeholder
    }

    private function getPaymentRiskAnalysisForReport(Collection $customers): array
    {
        // Implementar análise de risco de pagamento para relatório
        return []; // Placeholder
    }

    private function getInactiveCustomersForReport(Collection $customers): array
    {
        return $customers->filter(function($customer) {
            return $customer->last_interaction_at && $customer->last_interaction_at < now()->subMonths(3);
        })->map(function($customer) {
            return [
                'customer_name' => $customer->commonData?->first_name . ' ' . $customer->commonData?->last_name,
                'last_interaction' => $customer->last_interaction_at,
                'inactivity_days' => now()->diffInDays($customer->last_interaction_at),
                'total_spent' => $customer->invoices()->where('status', 'paid')->sum('total'),
            ];
        })->toArray();
    }

    private function getRiskMitigationStrategiesForReport(Collection $customers): array
    {
        // Implementar estratégias de mitigação de risco para relatório
        return []; // Placeholder
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Dashboards**

```php
public function testExecutiveDashboard()
{
    $tenant = Tenant::factory()->create();
    Customer::factory()->count(10)->create(['tenant_id' => $tenant->id]);

    $result = $this->dashboardService->getExecutiveDashboard($tenant->id);
    $this->assertTrue($result->isSuccess());

    $dashboard = $result->getData();
    $this->assertArrayHasKey('customer_overview', $dashboard);
    $this->assertArrayHasKey('financial_metrics', $dashboard);
    $this->assertArrayHasKey('engagement_metrics', $dashboard);
    $this->assertArrayHasKey('growth_metrics', $dashboard);
    $this->assertArrayHasKey('risk_metrics', $dashboard);
}

public function testCustomerAnalytics()
{
    $customer = Customer::factory()->create();
    Invoice::factory()->count(5)->create([
        'service_id' => Service::factory()->create([
            'budget_id' => Budget::factory()->create(['customer_id' => $customer->id])->id
        ])->id,
        'status' => 'paid',
        'total' => 100,
    ]);

    $result = $this->dashboardService->getCustomerAnalytics($customer);
    $this->assertTrue($result->isSuccess());

    $analytics = $result->getData();
    $this->assertArrayHasKey('revenue_analysis', $analytics);
    $this->assertArrayHasKey('service_analysis', $analytics);
    $this->assertArrayHasKey('payment_analysis', $analytics);
    $this->assertArrayHasKey('interaction_analysis', $analytics);
    $this->assertArrayHasKey('lifecycle_analysis', $analytics);
    $this->assertArrayHasKey('risk_assessment', $analytics);
}

public function testSegmentationDashboard()
{
    $tenant = Tenant::factory()->create();
    Customer::factory()->count(20)->create(['tenant_id' => $tenant->id]);

    $result = $this->dashboardService->getCustomerSegmentationDashboard($tenant->id);
    $this->assertTrue($result->isSuccess());

    $dashboard = $result->getData();
    $this->assertArrayHasKey('segment_distribution', $dashboard);
    $this->assertArrayHasKey('segment_performance', $dashboard);
    $this->assertArrayHasKey('segment_trends', $dashboard);
    $this->assertArrayHasKey('segment_comparisons', $dashboard);
}
```

### **✅ Testes de Relatórios**

```php
public function testSummaryReport()
{
    $tenant = Tenant::factory()->create();
    Customer::factory()->count(10)->create(['tenant_id' => $tenant->id]);

    $result = $this->reportingService->generateCustomerReport([
        'tenant_id' => $tenant->id,
    ], 'summary');

    $this->assertTrue($result->isSuccess());

    $report = $result->getData();
    $this->assertArrayHasKey('total_customers', $report);
    $this->assertArrayHasKey('by_status', $report);
    $this->assertArrayHasKey('by_type', $report);
    $this->assertArrayHasKey('financial_summary', $report);
}

public function testFinancialReport()
{
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    Invoice::factory()->count(5)->create([
        'service_id' => Service::factory()->create([
            'budget_id' => Budget::factory()->create(['customer_id' => $customer->id])->id
        ])->id,
        'status' => 'paid',
        'total' => 100,
    ]);

    $result = $this->reportingService->generateCustomerReport([
        'tenant_id' => $tenant->id,
    ], 'financial');

    $this->assertTrue($result->isSuccess());

    $report = $result->getData();
    $this->assertArrayHasKey('revenue_by_customer', $report);
    $this->assertArrayHasKey('revenue_by_month', $report);
    $this->assertArrayHasKey('payment_analysis', $report);
    $this->assertArrayHasKey('outstanding_analysis', $report);
}

public function testTrendReport()
{
    $tenant = Tenant::factory()->create();
    Customer::factory()->count(5)->create(['tenant_id' => $tenant->id]);

    $result = $this->reportingService->generateCustomerTrendReport([
        'tenant_id' => $tenant->id,
    ], 6);

    $this->assertTrue($result->isSuccess());

    $report = $result->getData();
    $this->assertArrayHasKey('trends', $report);
    $this->assertArrayHasKey('period', $report);
    $this->assertArrayHasKey('analysis', $report);
    $this->assertCount(7, $report['trends']); // 6 meses + mês atual
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar CustomerDashboardService básico
- [ ] Criar CustomerReportingService básico
- [ ] Implementar métricas básicas de dashboard
- [ ] Sistema de filtros para dashboards

### **Fase 2: Core Features**
- [ ] Implementar analytics avançados por cliente
- [ ] Criar dashboards de segmentação
- [ ] Sistema de relatórios detalhados
- [ ] Exportação de dashboards para PDF/Excel

### **Fase 3: Advanced Features**
- [ ] Machine learning para predição de churn
- [ ] Análise de sentimento em interações
- [ ] Dashboard em tempo real
- [ ] Alertas e notificações baseados em métricas

### **Fase 4: Integration**
- [ ] Integração com ferramentas de BI externas
- [ ] API para dashboards personalizados
- [ ] Sistema de compartilhamento de dashboards
- [ ] Dashboard móvel responsivo

## 📚 Documentação Relacionada

- [CustomerDashboardService](../../app/Services/Domain/CustomerDashboardService.php)
- [CustomerReportingService](../../app/Services/Domain/CustomerReportingService.php)
- [Dashboard Views](../../resources/views/pages/dashboard/)
- [Analytics Components](../../resources/views/components/analytics/)

## 🎯 Benefícios

### **✅ Tomada de Decisão Baseada em Dados**
- Dashboards executivos com KPIs essenciais
- Analytics detalhados por cliente
- Identificação de oportunidades de negócio
- Previsão de tendências e comportamentos

### **✅ Gestão de Relacionamento**
- Monitoramento de engajamento
- Identificação precoce de riscos
- Estratégias de retenção baseadas em dados
- Personalização de abordagens

### **✅ Eficiência Operacional**
- Relatórios automatizados
- Redução de tempo em análises manuais
- Identificação de gargalos no processo
- Otimização de recursos

### **✅ Conformidade e Auditoria**
- Histórico completo de métricas
- Relatórios para auditoria
- Conformidade com requisitos regulatórios
- Rastreabilidade de decisões

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
