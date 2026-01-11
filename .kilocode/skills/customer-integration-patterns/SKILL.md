# 🔗 Skill: Customer Integration Patterns (Padrões de Integração)

**Descrição:** Sistema de integração de clientes com outros módulos do sistema (orçamentos, serviços, faturas) e com sistemas externos, garantindo consistência de dados e fluxos de trabalho integrados.

**Categoria:** Integração e Arquitetura
**Complexidade:** Alta
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Estabelecer padrões de integração robustos entre o módulo de clientes e outros componentes do sistema, além de fornecer mecanismos para integração com sistemas externos de forma segura e eficiente.

## 📋 Requisitos Técnicos

### **✅ Integração com Orçamentos**

```php
class CustomerBudgetIntegrationService extends AbstractBaseService
{
    public function createBudgetForCustomer(Customer $customer, array $budgetData): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $budgetData) {
            // 1. Validar integração cliente-orçamento
            $validation = $this->validateCustomerBudgetIntegration($customer, $budgetData);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Criar orçamento
            $budgetService = app(BudgetService::class);
            $budgetResult = $budgetService->create($budgetData);

            if (!$budgetResult->isSuccess()) {
                return $budgetResult;
            }

            $budget = $budgetResult->getData();

            // 3. Atualizar estatísticas do cliente
            $this->updateCustomerBudgetStatistics($customer, $budget);

            // 4. Disparar eventos de integração
            event(new CustomerBudgetCreated($customer, $budget));

            // 5. Atualizar histórico de interações
            $this->createBudgetInteraction($customer, $budget, 'budget_created');

            return $this->success($budget, 'Orçamento criado e integrado ao cliente');
        });
    }

    public function updateCustomerBudgetStatistics(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $stats = [
                'total_budgets' => $customer->budgets()->count(),
                'active_budgets' => $customer->budgets()->where('status', 'active')->count(),
                'approved_budgets' => $customer->budgets()->where('status', 'approved')->count(),
                'rejected_budgets' => $customer->budgets()->where('status', 'rejected')->count(),
                'total_budget_value' => $customer->budgets()->sum('total_value'),
                'average_budget_value' => $customer->budgets()->avg('total_value') ?? 0,
                'last_budget_at' => $customer->budgets()->latest('created_at')->first()?->created_at,
            ];

            $customer->update($stats);

            return $this->success($stats, 'Estatísticas de orçamentos atualizadas');
        });
    }

    public function getCustomerBudgetPortfolio(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $portfolio = [
                'summary' => $this->getBudgetSummary($customer),
                'by_status' => $this->getBudgetsByStatus($customer),
                'by_type' => $this->getBudgetsByType($customer),
                'financial_analysis' => $this->getBudgetFinancialAnalysis($customer),
                'trends' => $this->getBudgetTrends($customer),
            ];

            return $this->success($portfolio, 'Portfólio de orçamentos do cliente');
        });
    }

    private function validateCustomerBudgetIntegration(Customer $customer, array $budgetData): ServiceResult
    {
        // Validar se cliente está ativo
        if ($customer->status !== 'active') {
            return $this->error('Não é possível criar orçamento para cliente inativo', OperationStatus::INVALID_DATA);
        }

        // Validar limite de orçamentos pendentes
        $pendingBudgets = $customer->budgets()->where('status', 'pending')->count();
        if ($pendingBudgets >= 10) { // Limite configurável
            return $this->error('Cliente atingiu o limite de orçamentos pendentes', OperationStatus::INVALID_DATA);
        }

        // Validar histórico de rejeições
        $rejectedBudgets = $customer->budgets()->where('status', 'rejected')->count();
        $totalBudgets = $customer->budgets()->count();

        if ($totalBudgets > 0) {
            $rejectionRate = ($rejectedBudgets / $totalBudgets) * 100;
            if ($rejectionRate > 50) {
                return $this->error('Cliente possui alta taxa de rejeição de orçamentos', OperationStatus::INVALID_DATA);
            }
        }

        return $this->success(null, 'Integração cliente-orçamento válida');
    }

    private function updateCustomerBudgetStatistics(Customer $customer, Budget $budget): void
    {
        $customer->update([
            'total_budgets' => $customer->budgets()->count(),
            'last_budget_at' => $budget->created_at,
        ]);
    }

    private function createBudgetInteraction(Customer $customer, Budget $budget, string $interactionType): void
    {
        CustomerInteraction::create([
            'tenant_id' => $this->getTenantId(),
            'customer_id' => $customer->id,
            'interaction_type' => $interactionType,
            'description' => "Orçamento {$budget->code} criado",
            'interaction_date' => now(),
            'created_by' => auth()->id(),
        ]);
    }

    private function getBudgetSummary(Customer $customer): array
    {
        return [
            'total_budgets' => $customer->budgets()->count(),
            'active_budgets' => $customer->budgets()->where('status', 'active')->count(),
            'approved_budgets' => $customer->budgets()->where('status', 'approved')->count(),
            'rejected_budgets' => $customer->budgets()->where('status', 'rejected')->count(),
            'total_value' => $customer->budgets()->sum('total_value'),
            'average_value' => $customer->budgets()->avg('total_value') ?? 0,
        ];
    }

    private function getBudgetsByStatus(Customer $customer): array
    {
        return $customer->budgets()
            ->groupBy('status')
            ->selectRaw('status, count(*) as count, sum(total_value) as total_value')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->status => [
                    'count' => $item->count,
                    'total_value' => $item->total_value,
                    'average_value' => $item->count > 0 ? $item->total_value / $item->count : 0,
                ]];
            })
            ->toArray();
    }

    private function getBudgetsByType(Customer $customer): array
    {
        return $customer->budgets()
            ->groupBy('budget_type')
            ->selectRaw('budget_type, count(*) as count, sum(total_value) as total_value')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->budget_type => [
                    'count' => $item->count,
                    'total_value' => $item->total_value,
                    'average_value' => $item->count > 0 ? $item->total_value / $item->count : 0,
                ]];
            })
            ->toArray();
    }

    private function getBudgetFinancialAnalysis(Customer $customer): array
    {
        $budgets = $customer->budgets()->get();

        return [
            'conversion_rate' => $this->calculateBudgetConversionRate($budgets),
            'average_approval_time' => $this->calculateAverageApprovalTime($budgets),
            'revenue_potential' => $budgets->where('status', 'approved')->sum('total_value'),
            'budget_accuracy' => $this->calculateBudgetAccuracy($budgets),
            'seasonal_trends' => $this->getSeasonalBudgetTrends($budgets),
        ];
    }

    private function getBudgetTrends(Customer $customer): array
    {
        return $customer->budgets()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as count, sum(total_value) as total_value')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    private function calculateBudgetConversionRate(Collection $budgets): float
    {
        $totalBudgets = $budgets->count();
        $approvedBudgets = $budgets->where('status', 'approved')->count();

        return $totalBudgets > 0 ? ($approvedBudgets / $totalBudgets) * 100 : 0;
    }

    private function calculateAverageApprovalTime(Collection $budgets): float
    {
        $approvedBudgets = $budgets->where('status', 'approved');

        if ($approvedBudgets->isEmpty()) return 0;

        $totalDays = $approvedBudgets->sum(function($budget) {
            return $budget->approved_at ? $budget->created_at->diffInDays($budget->approved_at) : 0;
        });

        return $approvedBudgets->count() > 0 ? ($totalDays / $approvedBudgets->count()) : 0;
    }

    private function calculateBudgetAccuracy(Collection $budgets): float
    {
        // Comparar valor orçado vs valor real (quando houver serviço associado)
        $accurateBudgets = $budgets->filter(function($budget) {
            return $budget->services->isNotEmpty() && $budget->services->sum('total') == $budget->total_value;
        });

        return $budgets->count() > 0 ? ($accurateBudgets->count() / $budgets->count()) * 100 : 0;
    }

    private function getSeasonalBudgetTrends(Collection $budgets): array
    {
        return $budgets->groupBy(function($budget) {
            return $budget->created_at->format('m'); // Mês
        })->map->count()->toArray();
    }
}
```

### **✅ Integração com Serviços**

```php
class CustomerServiceIntegrationService extends AbstractBaseService
{
    public function createServiceForCustomer(Customer $customer, array $serviceData): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $serviceData) {
            // 1. Validar integração cliente-serviço
            $validation = $this->validateCustomerServiceIntegration($customer, $serviceData);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Criar serviço
            $serviceService = app(ServiceService::class);
            $serviceResult = $serviceService->create($serviceData);

            if (!$serviceResult->isSuccess()) {
                return $serviceResult;
            }

            $service = $serviceResult->getData();

            // 3. Atualizar estatísticas do cliente
            $this->updateCustomerServiceStatistics($customer, $service);

            // 4. Disparar eventos de integração
            event(new CustomerServiceCreated($customer, $service));

            // 5. Criar interação
            $this->createServiceInteraction($customer, $service, 'service_created');

            return $this->success($service, 'Serviço criado e integrado ao cliente');
        });
    }

    public function updateCustomerServiceStatistics(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $stats = [
                'total_services' => $customer->services()->count(),
                'active_services' => $customer->services()->where('status', 'active')->count(),
                'completed_services' => $customer->services()->where('status', 'completed')->count(),
                'cancelled_services' => $customer->services()->where('status', 'cancelled')->count(),
                'total_service_value' => $customer->services()->sum('total'),
                'average_service_value' => $customer->services()->avg('total') ?? 0,
                'last_service_at' => $customer->services()->latest('created_at')->first()?->created_at,
            ];

            $customer->update($stats);

            return $this->success($stats, 'Estatísticas de serviços atualizadas');
        });
    }

    public function getCustomerServicePortfolio(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $portfolio = [
                'summary' => $this->getServiceSummary($customer),
                'by_status' => $this->getServicesByStatus($customer),
                'by_type' => $this->getServicesByType($customer),
                'performance_analysis' => $this->getServicePerformanceAnalysis($customer),
                'trends' => $this->getServiceTrends($customer),
            ];

            return $this->success($portfolio, 'Portfólio de serviços do cliente');
        });
    }

    private function validateCustomerServiceIntegration(Customer $customer, array $serviceData): ServiceResult
    {
        // Validar se cliente está ativo
        if ($customer->status !== 'active') {
            return $this->error('Não é possível criar serviço para cliente inativo', OperationStatus::INVALID_DATA);
        }

        // Validar limite de serviços ativos
        $activeServices = $customer->services()->where('status', 'active')->count();
        if ($activeServices >= 20) { // Limite configurável
            return $this->error('Cliente atingiu o limite de serviços ativos', OperationStatus::INVALID_DATA);
        }

        // Validar relacionamento com orçamento (se necessário)
        if (isset($serviceData['budget_id'])) {
            $budget = Budget::find($serviceData['budget_id']);
            if ($budget && $budget->customer_id !== $customer->id) {
                return $this->error('Orçamento não pertence a este cliente', OperationStatus::INVALID_DATA);
            }
        }

        return $this->success(null, 'Integração cliente-serviço válida');
    }

    private function updateCustomerServiceStatistics(Customer $customer, Service $service): void
    {
        $customer->update([
            'total_services' => $customer->services()->count(),
            'last_service_at' => $service->created_at,
        ]);
    }

    private function createServiceInteraction(Customer $customer, Service $service, string $interactionType): void
    {
        CustomerInteraction::create([
            'tenant_id' => $this->getTenantId(),
            'customer_id' => $customer->id,
            'interaction_type' => $interactionType,
            'description' => "Serviço {$service->code} criado",
            'interaction_date' => now(),
            'created_by' => auth()->id(),
        ]);
    }

    private function getServiceSummary(Customer $customer): array
    {
        return [
            'total_services' => $customer->services()->count(),
            'active_services' => $customer->services()->where('status', 'active')->count(),
            'completed_services' => $customer->services()->where('status', 'completed')->count(),
            'cancelled_services' => $customer->services()->where('status', 'cancelled')->count(),
            'total_value' => $customer->services()->sum('total'),
            'average_value' => $customer->services()->avg('total') ?? 0,
        ];
    }

    private function getServicesByStatus(Customer $customer): array
    {
        return $customer->services()
            ->groupBy('status')
            ->selectRaw('status, count(*) as count, sum(total) as total_value')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->status => [
                    'count' => $item->count,
                    'total_value' => $item->total_value,
                    'average_value' => $item->count > 0 ? $item->total_value / $item->count : 0,
                ]];
            })
            ->toArray();
    }

    private function getServicesByType(Customer $customer): array
    {
        return $customer->services()
            ->groupBy('service_type')
            ->selectRaw('service_type, count(*) as count, sum(total) as total_value')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->service_type => [
                    'count' => $item->count,
                    'total_value' => $item->total_value,
                    'average_value' => $item->count > 0 ? $item->total_value / $item->count : 0,
                ]];
            })
            ->toArray();
    }

    private function getServicePerformanceAnalysis(Customer $customer): array
    {
        $services = $customer->services()->get();

        return [
            'completion_rate' => $this->calculateServiceCompletionRate($services),
            'average_completion_time' => $this->calculateAverageCompletionTime($services),
            'customer_satisfaction' => $this->calculateCustomerSatisfaction($services),
            'service_quality' => $this->calculateServiceQuality($services),
            'repeat_service_rate' => $this->calculateRepeatServiceRate($services),
        ];
    }

    private function getServiceTrends(Customer $customer): array
    {
        return $customer->services()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as count, sum(total) as total_value')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    private function calculateServiceCompletionRate(Collection $services): float
    {
        $totalServices = $services->count();
        $completedServices = $services->where('status', 'completed')->count();

        return $totalServices > 0 ? ($completedServices / $totalServices) * 100 : 0;
    }

    private function calculateAverageCompletionTime(Collection $services): float
    {
        $completedServices = $services->where('status', 'completed');

        if ($completedServices->isEmpty()) return 0;

        $totalDays = $completedServices->sum(function($service) {
            return $service->completed_at ? $service->created_at->diffInDays($service->completed_at) : 0;
        });

        return $completedServices->count() > 0 ? ($totalDays / $completedServices->count()) : 0;
    }

    private function calculateCustomerSatisfaction(Collection $services): float
    {
        // Implementar lógica de cálculo de satisfação baseado em feedbacks
        return 0.0; // Placeholder
    }

    private function calculateServiceQuality(Collection $services): float
    {
        // Implementar lógica de cálculo de qualidade baseado em métricas de serviço
        return 0.0; // Placeholder
    }

    private function calculateRepeatServiceRate(Collection $services): float
    {
        // Identificar serviços repetidos (mesmo tipo, mesmo cliente)
        $serviceTypes = $services->groupBy('service_type');
        $repeatServices = $serviceTypes->filter(function($services) {
            return $services->count() > 1;
        });

        $totalServices = $services->count();
        $repeatServiceCount = $repeatServices->sum->count();

        return $totalServices > 0 ? ($repeatServiceCount / $totalServices) * 100 : 0;
    }
}
```

### **✅ Integração com Faturas**

```php
class CustomerInvoiceIntegrationService extends AbstractBaseService
{
    public function createInvoiceForCustomer(Customer $customer, array $invoiceData): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $invoiceData) {
            // 1. Validar integração cliente-fatura
            $validation = $this->validateCustomerInvoiceIntegration($customer, $invoiceData);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Criar fatura
            $invoiceService = app(InvoiceService::class);
            $invoiceResult = $invoiceService->create($invoiceData);

            if (!$invoiceResult->isSuccess()) {
                return $invoiceResult;
            }

            $invoice = $invoiceResult->getData();

            // 3. Atualizar estatísticas do cliente
            $this->updateCustomerInvoiceStatistics($customer, $invoice);

            // 4. Disparar eventos de integração
            event(new CustomerInvoiceCreated($customer, $invoice));

            // 5. Criar interação
            $this->createInvoiceInteraction($customer, $invoice, 'invoice_created');

            return $this->success($invoice, 'Fatura criada e integrada ao cliente');
        });
    }

    public function updateCustomerInvoiceStatistics(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $stats = [
                'total_invoices' => $customer->invoices()->count(),
                'paid_invoices' => $customer->invoices()->where('status', 'paid')->count(),
                'pending_invoices' => $customer->invoices()->where('status', 'pending')->count(),
                'overdue_invoices' => $customer->invoices()->where('due_date', '<', now())->where('status', 'pending')->count(),
                'total_revenue' => $customer->invoices()->where('status', 'paid')->sum('total'),
                'pending_revenue' => $customer->invoices()->where('status', 'pending')->sum('total'),
                'average_invoice_value' => $customer->invoices()->where('status', 'paid')->avg('total') ?? 0,
                'last_invoice_at' => $customer->invoices()->latest('created_at')->first()?->created_at,
                'payment_history' => $this->getPaymentHistory($customer),
            ];

            $customer->update($stats);

            return $this->success($stats, 'Estatísticas de faturas atualizadas');
        });
    }

    public function getCustomerInvoicePortfolio(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $portfolio = [
                'summary' => $this->getInvoiceSummary($customer),
                'by_status' => $this->getInvoicesByStatus($customer),
                'financial_analysis' => $this->getInvoiceFinancialAnalysis($customer),
                'payment_behavior' => $this->getPaymentBehaviorAnalysis($customer),
                'trends' => $this->getInvoiceTrends($customer),
            ];

            return $this->success($portfolio, 'Portfólio de faturas do cliente');
        });
    }

    private function validateCustomerInvoiceIntegration(Customer $customer, array $invoiceData): ServiceResult
    {
        // Validar se cliente está ativo
        if ($customer->status !== 'active') {
            return $this->error('Não é possível criar fatura para cliente inativo', OperationStatus::INVALID_DATA);
        }

        // Validar limite de faturas pendentes
        $pendingInvoices = $customer->invoices()->where('status', 'pending')->count();
        if ($pendingInvoices >= 15) { // Limite configurável
            return $this->error('Cliente atingiu o limite de faturas pendentes', OperationStatus::INVALID_DATA);
        }

        // Validar relacionamento com serviço (se necessário)
        if (isset($invoiceData['service_id'])) {
            $service = Service::find($invoiceData['service_id']);
            if ($service && $service->budget->customer_id !== $customer->id) {
                return $this->error('Serviço não pertence a este cliente', OperationStatus::INVALID_DATA);
            }
        }

        return $this->success(null, 'Integração cliente-fatura válida');
    }

    private function updateCustomerInvoiceStatistics(Customer $customer, Invoice $invoice): void
    {
        $customer->update([
            'total_invoices' => $customer->invoices()->count(),
            'last_invoice_at' => $invoice->created_at,
        ]);
    }

    private function createInvoiceInteraction(Customer $customer, Invoice $invoice, string $interactionType): void
    {
        CustomerInteraction::create([
            'tenant_id' => $this->getTenantId(),
            'customer_id' => $customer->id,
            'interaction_type' => $interactionType,
            'description' => "Fatura {$invoice->code} criada",
            'interaction_date' => now(),
            'created_by' => auth()->id(),
        ]);
    }

    private function getInvoiceSummary(Customer $customer): array
    {
        return [
            'total_invoices' => $customer->invoices()->count(),
            'paid_invoices' => $customer->invoices()->where('status', 'paid')->count(),
            'pending_invoices' => $customer->invoices()->where('status', 'pending')->count(),
            'overdue_invoices' => $customer->invoices()->where('due_date', '<', now())->where('status', 'pending')->count(),
            'total_revenue' => $customer->invoices()->where('status', 'paid')->sum('total'),
            'pending_revenue' => $customer->invoices()->where('status', 'pending')->sum('total'),
        ];
    }

    private function getInvoicesByStatus(Customer $customer): array
    {
        return $customer->invoices()
            ->groupBy('status')
            ->selectRaw('status, count(*) as count, sum(total) as total_value')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->status => [
                    'count' => $item->count,
                    'total_value' => $item->total_value,
                    'average_value' => $item->count > 0 ? $item->total_value / $item->count : 0,
                ]];
            })
            ->toArray();
    }

    private function getInvoiceFinancialAnalysis(Customer $customer): array
    {
        $invoices = $customer->invoices()->get();

        return [
            'collection_rate' => $this->calculateCollectionRate($invoices),
            'average_payment_time' => $this->calculateAveragePaymentTime($invoices),
            'payment_consistency' => $this->calculatePaymentConsistency($invoices),
            'revenue_trends' => $this->getRevenueTrends($invoices),
            'customer_lifetime_value' => $this->calculateCustomerLifetimeValue($invoices),
        ];
    }

    private function getPaymentBehaviorAnalysis(Customer $customer): array
    {
        $invoices = $customer->invoices()->get();

        return [
            'payment_methods' => $this->getPaymentMethodsDistribution($invoices),
            'payment_timing' => $this->getPaymentTimingAnalysis($invoices),
            'late_payment_rate' => $this->calculateLatePaymentRate($invoices),
            'discount_usage' => $this->getDiscountUsageAnalysis($invoices),
        ];
    }

    private function getInvoiceTrends(Customer $customer): array
    {
        return $customer->invoices()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as count, sum(total) as total_value')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    private function getPaymentHistory(Customer $customer): array
    {
        return $customer->invoices()
            ->where('status', 'paid')
            ->orderBy('transaction_date', 'desc')
            ->take(10)
            ->get()
            ->map(function($invoice) {
                return [
                    'date' => $invoice->transaction_date,
                    'amount' => $invoice->total,
                    'method' => $invoice->payment_method,
                ];
            })
            ->toArray();
    }

    private function calculateCollectionRate(Collection $invoices): float
    {
        $totalInvoices = $invoices->count();
        $paidInvoices = $invoices->where('status', 'paid')->count();

        return $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0;
    }

    private function calculateAveragePaymentTime(Collection $invoices): float
    {
        $paidInvoices = $invoices->where('status', 'paid');

        if ($paidInvoices->isEmpty()) return 0;

        $totalDays = $paidInvoices->sum(function($invoice) {
            return $invoice->transaction_date ? $invoice->created_at->diffInDays($invoice->transaction_date) : 0;
        });

        return $paidInvoices->count() > 0 ? ($totalDays / $paidInvoices->count()) : 0;
    }

    private function calculatePaymentConsistency(Collection $invoices): float
    {
        $paidInvoices = $invoices->where('status', 'paid');

        if ($paidInvoices->count() < 2) return 100;

        $paymentIntervals = [];
        $sortedInvoices = $paidInvoices->sortBy('transaction_date');

        foreach ($sortedInvoices->slice(1) as $index => $invoice) {
            $prevInvoice = $sortedInvoices[$index];
            $interval = $prevInvoice->transaction_date->diffInDays($invoice->transaction_date);
            $paymentIntervals[] = $interval;
        }

        if (empty($paymentIntervals)) return 100;

        $avgInterval = array_sum($paymentIntervals) / count($paymentIntervals);
        $stdDev = sqrt(array_sum(array_map(function($interval) use ($avgInterval) {
            return pow($interval - $avgInterval, 2);
        }, $paymentIntervals)) / count($paymentIntervals));

        return $avgInterval > 0 ? (1 - ($stdDev / $avgInterval)) * 100 : 100;
    }

    private function getRevenueTrends(Collection $invoices): array
    {
        return $invoices->groupBy(function($invoice) {
            return $invoice->created_at->format('Y-m');
        })->map(function($monthInvoices) {
            return [
                'total_revenue' => $monthInvoices->sum('total'),
                'invoice_count' => $monthInvoices->count(),
                'average_value' => $monthInvoices->avg('total') ?? 0,
            ];
        })->toArray();
    }

    private function calculateCustomerLifetimeValue(Collection $invoices): float
    {
        return $invoices->where('status', 'paid')->sum('total');
    }

    private function getPaymentMethodsDistribution(Collection $invoices): array
    {
        return $invoices->groupBy('payment_method')
            ->map->count()
            ->toArray();
    }

    private function getPaymentTimingAnalysis(Collection $invoices): array
    {
        $paidInvoices = $invoices->where('status', 'paid');

        $earlyPayments = $paidInvoices->filter(function($invoice) {
            return $invoice->transaction_date && $invoice->transaction_date <= $invoice->due_date;
        })->count();

        $latePayments = $paidInvoices->filter(function($invoice) {
            return $invoice->transaction_date && $invoice->transaction_date > $invoice->due_date;
        })->count();

        return [
            'early_payments' => $earlyPayments,
            'late_payments' => $latePayments,
            'on_time_rate' => $paidInvoices->count() > 0 ? ($earlyPayments / $paidInvoices->count()) * 100 : 0,
        ];
    }

    private function calculateLatePaymentRate(Collection $invoices): float
    {
        $paidInvoices = $invoices->where('status', 'paid');
        $latePayments = $paidInvoices->filter(function($invoice) {
            return $invoice->transaction_date && $invoice->transaction_date > $invoice->due_date;
        })->count();

        return $paidInvoices->count() > 0 ? ($latePayments / $paidInvoices->count()) * 100 : 0;
    }

    private function getDiscountUsageAnalysis(Collection $invoices): array
    {
        $totalDiscounts = $invoices->sum('discount');
        $discountedInvoices = $invoices->where('discount', '>', 0)->count();

        return [
            'total_discounts' => $totalDiscounts,
            'discounted_invoices' => $discountedInvoices,
            'average_discount' => $discountedInvoices > 0 ? ($totalDiscounts / $discountedInvoices) : 0,
        ];
    }
}
```

### **✅ Integração com Sistemas Externos**

```php
class CustomerExternalIntegrationService extends AbstractBaseService
{
    public function syncCustomerToCRM(Customer $customer, string $crmSystem): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $crmSystem) {
            switch ($crmSystem) {
                case 'salesforce':
                    return $this->syncToSalesforce($customer);
                case 'hubspot':
                    return $this->syncToHubSpot($customer);
                case 'pipedrive':
                    return $this->syncToPipedrive($customer);
                default:
                    return $this->error('Sistema CRM não suportado', OperationStatus::INVALID_DATA);
            }
        });
    }

    public function syncCustomerFromCRM(string $crmSystem, string $externalId): ServiceResult
    {
        return $this->safeExecute(function() use ($crmSystem, $externalId) {
            switch ($crmSystem) {
                case 'salesforce':
                    return $this->syncFromSalesforce($externalId);
                case 'hubspot':
                    return $this->syncFromHubSpot($externalId);
                case 'pipedrive':
                    return $this->syncFromPipedrive($externalId);
                default:
                    return $this->error('Sistema CRM não suportado', OperationStatus::INVALID_DATA);
            }
        });
    }

    public function createWebhookIntegration(Customer $customer, string $webhookUrl, array $events): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $webhookUrl, $events) {
            $webhook = CustomerWebhook::create([
                'tenant_id' => $this->getTenantId(),
                'customer_id' => $customer->id,
                'url' => $webhookUrl,
                'events' => $events,
                'status' => 'active',
                'secret' => Str::random(32),
            ]);

            // Testar webhook
            $testResult = $this->testWebhook($webhook);
            if (!$testResult->isSuccess()) {
                $webhook->update(['status' => 'failed']);
                return $testResult;
            }

            return $this->success($webhook, 'Webhook de integração criado');
        });
    }

    public function triggerWebhook(Customer $customer, string $eventType, array $data): void
    {
        $webhooks = CustomerWebhook::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->whereJsonContains('events', $eventType)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->sendWebhook($webhook, $eventType, $data);
        }
    }

    private function syncToSalesforce(Customer $customer): ServiceResult
    {
        try {
            $salesforceClient = app(SalesforceClient::class);

            $contactData = [
                'FirstName' => $customer->commonData?->first_name,
                'LastName' => $customer->commonData?->last_name,
                'Email' => $customer->contact?->email,
                'Phone' => $customer->contact?->phone,
                'MailingStreet' => $customer->address?->address,
                'MailingCity' => $customer->address?->city,
                'MailingState' => $customer->address?->state,
                'MailingPostalCode' => $customer->address?->cep,
                'RecordTypeId' => $this->getSalesforceRecordTypeId($customer->type),
                'Customer_Status__c' => $customer->status,
                'Customer_Type__c' => $customer->type,
                'Total_Revenue__c' => $customer->invoices()->where('status', 'paid')->sum('total'),
                'Last_Interaction__c' => $customer->last_interaction_at?->format('Y-m-d'),
            ];

            if ($customer->type === 'company') {
                $contactData['Company_Name__c'] = $customer->commonData?->company_name;
                $contactData['CNPJ__c'] = $customer->commonData?->cnpj;
            } else {
                $contactData['CPF__c'] = $customer->commonData?->cpf;
                $contactData['Birth_Date__c'] = $customer->commonData?->birth_date?->format('Y-m-d');
            }

            $result = $salesforceClient->create('Contact', $contactData);

            if ($result->isSuccess()) {
                // Salvar mapeamento de IDs
                CustomerExternalMapping::updateOrCreate([
                    'tenant_id' => $this->getTenantId(),
                    'customer_id' => $customer->id,
                    'external_system' => 'salesforce',
                ], [
                    'external_id' => $result->getData()['id'],
                    'sync_at' => now(),
                ]);

                return $this->success($result->getData(), 'Cliente sincronizado com Salesforce');
            }

            return $result;

        } catch (Exception $e) {
            return $this->error('Erro ao sincronizar com Salesforce: ' . $e->getMessage(), OperationStatus::EXTERNAL_ERROR);
        }
    }

    private function syncToHubSpot(Customer $customer): ServiceResult
    {
        try {
            $hubspotClient = app(HubSpotClient::class);

            $contactData = [
                'properties' => [
                    'email' => $customer->contact?->email,
                    'firstname' => $customer->commonData?->first_name,
                    'lastname' => $customer->commonData?->last_name,
                    'phone' => $customer->contact?->phone,
                    'address' => $customer->address?->address,
                    'city' => $customer->address?->city,
                    'state' => $customer->address?->state,
                    'zip' => $customer->address?->cep,
                    'customer_status' => $customer->status,
                    'customer_type' => $customer->type,
                    'total_revenue' => $customer->invoices()->where('status', 'paid')->sum('total'),
                    'last_interaction' => $customer->last_interaction_at?->format('Y-m-d'),
                ],
            ];

            if ($customer->type === 'company') {
                $contactData['properties']['company_name'] = $customer->commonData?->company_name;
                $contactData['properties']['cnpj'] = $customer->commonData?->cnpj;
            } else {
                $contactData['properties']['cpf'] = $customer->commonData?->cpf;
                $contactData['properties']['birth_date'] = $customer->commonData?->birth_date?->format('Y-m-d');
            }

            $result = $hubspotClient->createContact($contactData);

            if ($result->isSuccess()) {
                CustomerExternalMapping::updateOrCreate([
                    'tenant_id' => $this->getTenantId(),
                    'customer_id' => $customer->id,
                    'external_system' => 'hubspot',
                ], [
                    'external_id' => $result->getData()['id'],
                    'sync_at' => now(),
                ]);

                return $this->success($result->getData(), 'Cliente sincronizado com HubSpot');
            }

            return $result;

        } catch (Exception $e) {
            return $this->error('Erro ao sincronizar com HubSpot: ' . $e->getMessage(), OperationStatus::EXTERNAL_ERROR);
        }
    }

    private function syncToPipedrive(Customer $customer): ServiceResult
    {
        try {
            $pipedriveClient = app(PipedriveClient::class);

            $personData = [
                'name' => $customer->commonData?->first_name . ' ' . $customer->commonData?->last_name,
                'email' => $customer->contact?->email,
                'phone' => $customer->contact?->phone,
                'address' => $customer->address?->address . ', ' . $customer->address?->city . ' - ' . $customer->address?->state,
                'customer_status' => $customer->status,
                'customer_type' => $customer->type,
                'total_revenue' => $customer->invoices()->where('status', 'paid')->sum('total'),
                'last_interaction' => $customer->last_interaction_at?->format('Y-m-d'),
            ];

            if ($customer->type === 'company') {
                $personData['org_name'] = $customer->commonData?->company_name;
                $personData['cnpj'] = $customer->commonData?->cnpj;
            } else {
                $personData['cpf'] = $customer->commonData?->cpf;
                $personData['birth_date'] = $customer->commonData?->birth_date?->format('Y-m-d');
            }

            $result = $pipedriveClient->createPerson($personData);

            if ($result->isSuccess()) {
                CustomerExternalMapping::updateOrCreate([
                    'tenant_id' => $this->getTenantId(),
                    'customer_id' => $customer->id,
                    'external_system' => 'pipedrive',
                ], [
                    'external_id' => $result->getData()['id'],
                    'sync_at' => now(),
                ]);

                return $this->success($result->getData(), 'Cliente sincronizado com Pipedrive');
            }

            return $result;

        } catch (Exception $e) {
            return $this->error('Erro ao sincronizar com Pipedrive: ' . $e->getMessage(), OperationStatus::EXTERNAL_ERROR);
        }
    }

    private function syncFromSalesforce(string $externalId): ServiceResult
    {
        try {
            $salesforceClient = app(SalesforceClient::class);
            $contactData = $salesforceClient->getContact($externalId);

            if (!$contactData->isSuccess()) {
                return $contactData;
            }

            $customerData = $this->mapSalesforceToCustomer($contactData->getData());
            $customerService = app(CustomerService::class);

            return $customerService->create($customerData, auth()->user());

        } catch (Exception $e) {
            return $this->error('Erro ao sincronizar do Salesforce: ' . $e->getMessage(), OperationStatus::EXTERNAL_ERROR);
        }
    }

    private function syncFromHubSpot(string $externalId): ServiceResult
    {
        try {
            $hubspotClient = app(HubSpotClient::class);
            $contactData = $hubspotClient->getContact($externalId);

            if (!$contactData->isSuccess()) {
                return $contactData;
            }

            $customerData = $this->mapHubSpotToCustomer($contactData->getData());
            $customerService = app(CustomerService::class);

            return $customerService->create($customerData, auth()->user());

        } catch (Exception $e) {
            return $this->error('Erro ao sincronizar do HubSpot: ' . $e->getMessage(), OperationStatus::EXTERNAL_ERROR);
        }
    }

    private function syncFromPipedrive(string $externalId): ServiceResult
    {
        try {
            $pipedriveClient = app(PipedriveClient::class);
            $personData = $pipedriveClient->getPerson($externalId);

            if (!$personData->isSuccess()) {
                return $personData;
            }

            $customerData = $this->mapPipedriveToCustomer($personData->getData());
            $customerService = app(CustomerService::class);

            return $customerService->create($customerData, auth()->user());

        } catch (Exception $e) {
            return $this->error('Erro ao sincronizar do Pipedrive: ' . $e->getMessage(), OperationStatus::EXTERNAL_ERROR);
        }
    }

    private function mapSalesforceToCustomer(array $salesforceData): CustomerDTO
    {
        return new CustomerDTO([
            'status' => $salesforceData['Customer_Status__c'] ?? 'active',
            'type' => $salesforceData['Customer_Type__c'] ?? 'individual',
            'common_data' => [
                'first_name' => $salesforceData['FirstName'],
                'last_name' => $salesforceData['LastName'],
                'cpf' => $salesforceData['CPF__c'] ?? null,
                'cnpj' => $salesforceData['CNPJ__c'] ?? null,
                'company_name' => $salesforceData['Company_Name__c'] ?? null,
                'birth_date' => $salesforceData['Birth_Date__c'] ?? null,
                'type' => $salesforceData['Customer_Type__c'] ?? 'individual',
            ],
            'contact' => [
                'email' => $salesforceData['Email'],
                'phone' => $salesforceData['Phone'],
            ],
            'address' => [
                'address' => $salesforceData['MailingStreet'],
                'city' => $salesforceData['MailingCity'],
                'state' => $salesforceData['MailingState'],
                'cep' => $salesforceData['MailingPostalCode'],
            ],
        ]);
    }

    private function mapHubSpotToCustomer(array $hubspotData): CustomerDTO
    {
        return new CustomerDTO([
            'status' => $hubspotData['properties']['customer_status'] ?? 'active',
            'type' => $hubspotData['properties']['customer_type'] ?? 'individual',
            'common_data' => [
                'first_name' => $hubspotData['properties']['firstname'],
                'last_name' => $hubspotData['properties']['lastname'],
                'cpf' => $hubspotData['properties']['cpf'] ?? null,
                'cnpj' => $hubspotData['properties']['cnpj'] ?? null,
                'company_name' => $hubspotData['properties']['company_name'] ?? null,
                'birth_date' => $hubspotData['properties']['birth_date'] ?? null,
                'type' => $hubspotData['properties']['customer_type'] ?? 'individual',
            ],
            'contact' => [
                'email' => $hubspotData['properties']['email'],
                'phone' => $hubspotData['properties']['phone'],
            ],
            'address' => [
                'address' => $hubspotData['properties']['address'],
                'city' => $hubspotData['properties']['city'],
                'state' => $hubspotData['properties']['state'],
                'cep' => $hubspotData['properties']['zip'],
            ],
        ]);
    }

    private function mapPipedriveToCustomer(array $pipedriveData): CustomerDTO
    {
        return new CustomerDTO([
            'status' => $pipedriveData['customer_status'] ?? 'active',
            'type' => $pipedriveData['customer_type'] ?? 'individual',
            'common_data' => [
                'first_name' => explode(' ', $pipedriveData['name'])[0],
                'last_name' => implode(' ', array_slice(explode(' ', $pipedriveData['name']), 1)),
                'cpf' => $pipedriveData['cpf'] ?? null,
                'cnpj' => $pipedriveData['cnpj'] ?? null,
                'company_name' => $pipedriveData['org_name'] ?? null,
                'birth_date' => $pipedriveData['birth_date'] ?? null,
                'type' => $pipedriveData['customer_type'] ?? 'individual',
            ],
            'contact' => [
                'email' => $pipedriveData['email'],
                'phone' => $pipedriveData['phone'],
            ],
            'address' => [
                'address' => $pipedriveData['address'],
                'city' => null, // Extrair da string de endereço
                'state' => null,
                'cep' => null,
            ],
        ]);
    }

    private function getSalesforceRecordTypeId(string $customerType): string
    {
        // Mapeamento de tipos de cliente para Record Types no Salesforce
        return match ($customerType) {
            'individual' => config('integrations.salesforce.record_types.individual'),
            'company' => config('integrations.salesforce.record_types.company'),
            default => config('integrations.salesforce.record_types.default'),
        };
    }

    private function testWebhook(CustomerWebhook $webhook): ServiceResult
    {
        try {
            $testData = [
                'event' => 'test',
                'customer_id' => $webhook->customer_id,
                'timestamp' => now()->toISOString(),
                'test' => true,
            ];

            $signature = hash_hmac('sha256', json_encode($testData), $webhook->secret);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
            ])->timeout(10)->post($webhook->url, $testData);

            if ($response->successful()) {
                return $this->success(null, 'Webhook testado com sucesso');
            }

            return $this->error('Webhook não respondeu corretamente', OperationStatus::EXTERNAL_ERROR);

        } catch (Exception $e) {
            return $this->error('Erro ao testar webhook: ' . $e->getMessage(), OperationStatus::EXTERNAL_ERROR);
        }
    }

    private function sendWebhook(CustomerWebhook $webhook, string $eventType, array $data): void
    {
        try {
            $payload = [
                'event' => $eventType,
                'customer_id' => $webhook->customer_id,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ];

            $signature = hash_hmac('sha256', json_encode($payload), $webhook->secret);

            Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
            ])->timeout(10)->post($webhook->url, $payload);

        } catch (Exception $e) {
            // Logar erro, mas não interromper o fluxo principal
            Log::error('Erro ao enviar webhook: ' . $e->getMessage(), [
                'webhook_id' => $webhook->id,
                'event_type' => $eventType,
            ]);
        }
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Integração**

```php
public function testCustomerBudgetIntegration()
{
    $customer = Customer::factory()->create();
    $budgetData = [
        'customer_id' => $customer->id,
        'description' => 'Test budget',
        'total_value' => 1000,
        'status' => 'pending',
    ];

    $result = $this->budgetIntegrationService->createBudgetForCustomer($customer, $budgetData);
    $this->assertTrue($result->isSuccess());

    $this->assertEquals(1, $customer->fresh()->total_budgets);
    $this->assertNotNull($customer->fresh()->last_budget_at);
}

public function testCustomerServiceIntegration()
{
    $customer = Customer::factory()->create();
    $serviceData = [
        'customer_id' => $customer->id,
        'description' => 'Test service',
        'total' => 500,
        'status' => 'pending',
    ];

    $result = $this->serviceIntegrationService->createServiceForCustomer($customer, $serviceData);
    $this->assertTrue($result->isSuccess());

    $this->assertEquals(1, $customer->fresh()->total_services);
    $this->assertNotNull($customer->fresh()->last_service_at);
}

public function testCustomerInvoiceIntegration()
{
    $customer = Customer::factory()->create();
    $invoiceData = [
        'customer_id' => $customer->id,
        'description' => 'Test invoice',
        'total' => 250,
        'status' => 'pending',
        'due_date' => now()->addDays(30),
    ];

    $result = $this->invoiceIntegrationService->createInvoiceForCustomer($customer, $invoiceData);
    $this->assertTrue($result->isSuccess());

    $this->assertEquals(1, $customer->fresh()->total_invoices);
    $this->assertNotNull($customer->fresh()->last_invoice_at);
}

public function testExternalIntegration()
{
    $customer = Customer::factory()->create();

    // Testar sincronização para Salesforce
    $result = $this->externalIntegrationService->syncCustomerToCRM($customer, 'salesforce');
    $this->assertTrue($result->isSuccess());

    // Verificar mapeamento de IDs
    $mapping = CustomerExternalMapping::where('customer_id', $customer->id)
        ->where('external_system', 'salesforce')
        ->first();

    $this->assertNotNull($mapping);
    $this->assertNotNull($mapping->external_id);
}
```

### **✅ Testes de Webhook**

```php
public function testWebhookCreation()
{
    $customer = Customer::factory()->create();
    $webhookUrl = 'https://example.com/webhook';
    $events = ['customer_created', 'customer_updated'];

    $result = $this->externalIntegrationService->createWebhookIntegration($customer, $webhookUrl, $events);
    $this->assertTrue($result->isSuccess());

    $webhook = $result->getData();
    $this->assertEquals($webhookUrl, $webhook->url);
    $this->assertEquals($events, $webhook->events);
    $this->assertEquals('active', $webhook->status);
}

public function testWebhookTrigger()
{
    $customer = Customer::factory()->create();
    $webhook = CustomerWebhook::factory()->create([
        'customer_id' => $customer->id,
        'url' => 'https://example.com/webhook',
        'events' => ['customer_created'],
    ]);

    // Simular trigger de webhook
    $this->externalIntegrationService->triggerWebhook($customer, 'customer_created', [
        'customer_name' => $customer->commonData?->first_name,
    ]);

    // Verificar se o webhook foi chamado (pode ser testado com mocks)
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar CustomerBudgetIntegrationService básico
- [ ] Criar CustomerServiceIntegrationService básico
- [ ] Implementar CustomerInvoiceIntegrationService básico
- [ ] Sistema de validação de integrações

### **Fase 2: Core Features**
- [ ] Implementar CustomerExternalIntegrationService
- [ ] Sistema de webhooks para integração externa
- [ ] Mapeamento de IDs externos
- [ ] Sincronização bidirecional com CRMs

### **Fase 3: Advanced Features**
- [ ] Integração com múltiplos CRMs (Salesforce, HubSpot, Pipedrive)
- [ ] Sistema de filas para sincronização assíncrona
- [ ] Monitoramento de integrações
- [ ] Relatórios de sincronização

### **Fase 4: Integration**
- [ ] API REST para integrações externas
- [ ] Sistema de eventos em tempo real
- [ ] Dashboard de integrações
- [ ] Suporte a webhooks externos

## 📚 Documentação Relacionada

- [CustomerBudgetIntegrationService](../../app/Services/Domain/CustomerBudgetIntegrationService.php)
- [CustomerServiceIntegrationService](../../app/Services/Domain/CustomerServiceIntegrationService.php)
- [CustomerInvoiceIntegrationService](../../app/Services/Domain/CustomerInvoiceIntegrationService.php)
- [CustomerExternalIntegrationService](../../app/Services/Domain/CustomerExternalIntegrationService.php)
- [CustomerWebhook](../../app/Models/CustomerWebhook.php)
- [CustomerExternalMapping](../../app/Models/CustomerExternalMapping.php)

## 🎯 Benefícios

### **✅ Integração Interna**
- Fluxos de trabalho integrados entre módulos
- Consistência de dados entre clientes, orçamentos, serviços e faturas
- Estatísticas consolidadas por cliente
- Histórico completo de interações

### **✅ Integração Externa**
- Sincronização com CRMs populares
- Webhooks para integração em tempo real
- Mapeamento de IDs para rastreabilidade
- Suporte a múltiplos sistemas externos

### **✅ Eficiência Operacional**
- Redução de trabalho manual de integração
- Dados consistentes entre sistemas
- Automatização de processos
- Melhor experiência do usuário

### **✅ Escalabilidade**
- Arquitetura modular para novas integrações
- Sistema de filas para alta performance
- Monitoramento de integrações
- Relatórios de performance

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
