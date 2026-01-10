# 🔗 Skill: Customer Integration Patterns (Padrões de Integração)

**Descrição:** Padrões de integração entre clientes, orçamentos, serviços e faturas no Easy Budget.

**Categoria:** Integração e Relacionamento
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Padronizar os padrões de integração entre clientes e outros módulos do sistema (orçamentos, serviços, faturas), garantindo consistência de dados, integridade referencial e fluxos de negócio bem definidos.

## 📋 Requisitos Técnicos

### **✅ Relacionamentos Principais**

```php
// Customer Model - Relacionamentos principais
class Customer extends Model
{
    // Relacionamentos com orçamentos
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function activeBudgets(): HasMany
    {
        return $this->hasMany(Budget::class)->where('status', 'active');
    }

    public function completedBudgets(): HasMany
    {
        return $this->hasMany(Budget::class)->where('status', 'completed');
    }

    // Relacionamentos com serviços
    public function services(): HasManyThrough
    {
        return $this->hasManyThrough(Service::class, Budget::class);
    }

    public function activeServices(): HasManyThrough
    {
        return $this->hasManyThrough(Service::class, Budget::class)
            ->where('services.status', 'active');
    }

    // Relacionamentos com faturas
    public function invoices(): HasManyThrough
    {
        return $this->hasManyThrough(Invoice::class, Service::class);
    }

    public function pendingInvoices(): HasManyThrough
    {
        return $this->hasManyThrough(Invoice::class, Service::class)
            ->where('invoices.status', 'pending');
    }

    public function paidInvoices(): HasManyThrough
    {
        return $this->hasManyThrough(Invoice::class, Service::class)
            ->where('invoices.status', 'paid');
    }

    // Relacionamentos com histórico
    public function interactions(): HasMany
    {
        return $this->hasMany(CustomerInteraction::class);
    }

    public function lifecycleHistory(): HasMany
    {
        return $this->hasMany(CustomerLifecycleHistory::class);
    }

    // Relacionamentos com dados auxiliares
    public function commonData(): BelongsTo
    {
        return $this->belongsTo(CommonData::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function businessData(): BelongsTo
    {
        return $this->belongsTo(BusinessData::class);
    }

    // Relacionamentos com tags
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CustomerTag::class, 'customer_tag_assignments')
            ->withTimestamps();
    }
}
```

### **✅ Estratégias de Carregamento**

```php
class CustomerIntegrationService extends AbstractBaseService
{
    public function getCustomerWithRelationships(int $customerId, array $relationships = []): ServiceResult
    {
        $defaultRelationships = [
            'commonData',
            'contact',
            'address',
            'businessData',
            'tags',
            'interactions',
            'lifecycleHistory',
        ];

        $relationships = array_merge($defaultRelationships, $relationships);

        $customer = Customer::with($relationships)->find($customerId);

        if (! $customer) {
            return $this->error('Cliente não encontrado', OperationStatus::NOT_FOUND);
        }

        return $this->success($customer, 'Cliente com relacionamentos');
    }

    public function getCustomerFinancialSummary(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $summary = [
                'total_budgets' => $customer->budgets()->count(),
                'active_budgets' => $customer->activeBudgets()->count(),
                'completed_budgets' => $customer->completedBudgets()->count(),
                'total_services' => $customer->services()->count(),
                'active_services' => $customer->activeServices()->count(),
                'total_invoices' => $customer->invoices()->count(),
                'pending_invoices' => $customer->pendingInvoices()->count(),
                'paid_invoices' => $customer->paidInvoices()->count(),
                'total_budget_value' => $customer->budgets()->sum('total_value'),
                'total_service_value' => $customer->services()->sum('total'),
                'total_invoice_value' => $customer->invoices()->sum('total'),
                'pending_amount' => $customer->invoices()->where('status', 'pending')->sum('total'),
                'paid_amount' => $customer->invoices()->where('status', 'paid')->sum('total'),
            ];

            return $this->success($summary, 'Resumo financeiro do cliente');
        });
    }

    public function getCustomerActivitySummary(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $summary = [
                'last_interaction' => $customer->interactions()->latest('interaction_date')->first()?->interaction_date,
                'interaction_count' => $customer->interactions()->count(),
                'last_budget_date' => $customer->budgets()->latest('created_at')->first()?->created_at,
                'last_service_date' => $customer->services()->latest('created_at')->first()?->created_at,
                'last_invoice_date' => $customer->invoices()->latest('created_at')->first()?->created_at,
                'current_lifecycle_stage' => $customer->lifecycle_stage,
                'stage_changed_at' => $customer->stage_changed_at,
                'tags_count' => $customer->tags()->count(),
                'active_tags' => $customer->tags()->where('is_active', true)->count(),
            ];

            return $this->success($summary, 'Resumo de atividade do cliente');
        });
    }
}
```

## 🏗️ Padrões de Integração

### **✅ Integração com Orçamentos**

```php
class CustomerBudgetIntegrationService extends AbstractBaseService
{
    public function createBudgetForCustomer(Customer $customer, BudgetDTO $dto, User $user): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $dto, $user) {
            // 1. Validar cliente
            if ($customer->status !== 'active') {
                return $this->error('Cliente não está ativo para receber orçamentos', OperationStatus::INVALID_DATA);
            }

            // 2. Criar orçamento
            $budgetService = app(BudgetService::class);
            $result = $budgetService->create($dto, $user);

            if (! $result->isSuccess()) {
                return $result;
            }

            $budget = $result->getData();

            // 3. Associar cliente ao orçamento
            $budget->update(['customer_id' => $customer->id]);

            // 4. Atualizar último contato do cliente
            $customer->update(['last_budget_at' => now()]);

            // 5. Disparar eventos
            event(new BudgetCreatedForCustomer($customer, $budget, $user));

            return $this->success($budget, 'Orçamento criado para cliente');
        });
    }

    public function getCustomerBudgets(Customer $customer, array $filters = []): ServiceResult
    {
        $query = $customer->budgets();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['with_services'])) {
            $query->with('services');
        }

        if (isset($filters['with_invoices'])) {
            $query->with('services.invoices');
        }

        $budgets = $query->with(['customer.commonData', 'customer.contact'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($budgets, 'Orçamentos do cliente');
    }

    public function updateCustomerBudget(Customer $customer, Budget $budget, BudgetDTO $dto): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $budget, $dto) {
            // 1. Validar pertencimento
            if ($budget->customer_id !== $customer->id) {
                return $this->error('Orçamento não pertence ao cliente', OperationStatus::INVALID_DATA);
            }

            // 2. Atualizar orçamento
            $budgetService = app(BudgetService::class);
            $result = $budgetService->update($budget, $dto);

            if ($result->isSuccess()) {
                // 3. Atualizar último contato do cliente
                $customer->update(['last_budget_at' => now()]);
            }

            return $result;
        });
    }

    public function deleteCustomerBudget(Customer $customer, Budget $budget): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $budget) {
            // 1. Validar pertencimento
            if ($budget->customer_id !== $customer->id) {
                return $this->error('Orçamento não pertence ao cliente', OperationStatus::INVALID_DATA);
            }

            // 2. Verificar dependências
            if ($budget->services()->exists()) {
                return $this->error('Não é possível excluir orçamento com serviços vinculados', OperationStatus::INVALID_DATA);
            }

            // 3. Excluir orçamento
            $budgetService = app(BudgetService::class);
            $result = $budgetService->delete($budget);

            if ($result->isSuccess()) {
                // 4. Atualizar estatísticas do cliente
                $this->updateCustomerBudgetStats($customer);
            }

            return $result;
        });
    }

    private function updateCustomerBudgetStats(Customer $customer): void
    {
        $stats = [
            'total_budgets' => $customer->budgets()->count(),
            'active_budgets' => $customer->activeBudgets()->count(),
            'completed_budgets' => $customer->completedBudgets()->count(),
            'total_budget_value' => $customer->budgets()->sum('total_value'),
        ];

        $customer->update($stats);
    }
}
```

### **✅ Integração com Serviços**

```php
class CustomerServiceIntegrationService extends AbstractBaseService
{
    public function createServiceForCustomer(Customer $customer, ServiceDTO $dto, User $user): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $dto, $user) {
            // 1. Validar cliente
            if ($customer->status !== 'active') {
                return $this->error('Cliente não está ativo para receber serviços', OperationStatus::INVALID_DATA);
            }

            // 2. Verificar orçamento
            $budget = Budget::find($dto->budget_id);
            if (! $budget || $budget->customer_id !== $customer->id) {
                return $this->error('Orçamento não encontrado ou não pertence ao cliente', OperationStatus::INVALID_DATA);
            }

            // 3. Criar serviço
            $serviceService = app(ServiceService::class);
            $result = $serviceService->create($dto, $user);

            if (! $result->isSuccess()) {
                return $result;
            }

            $service = $result->getData();

            // 4. Atualizar último contato do cliente
            $customer->update(['last_service_at' => now()]);

            // 5. Disparar eventos
            event(new ServiceCreatedForCustomer($customer, $service, $user));

            return $this->success($service, 'Serviço criado para cliente');
        });
    }

    public function getCustomerServices(Customer $customer, array $filters = []): ServiceResult
    {
        $query = $customer->services();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['with_invoices'])) {
            $query->with('invoices');
        }

        $services = $query->with([
            'budget.customer.commonData',
            'budget.customer.contact',
            'items.product',
        ])->orderBy('created_at', 'desc')->get();

        return $this->success($services, 'Serviços do cliente');
    }

    public function updateCustomerService(Customer $customer, Service $service, ServiceDTO $dto): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $service, $dto) {
            // 1. Validar pertencimento
            if ($service->budget->customer_id !== $customer->id) {
                return $this->error('Serviço não pertence ao cliente', OperationStatus::INVALID_DATA);
            }

            // 2. Atualizar serviço
            $serviceService = app(ServiceService::class);
            $result = $serviceService->update($service, $dto);

            if ($result->isSuccess()) {
                // 3. Atualizar último contato do cliente
                $customer->update(['last_service_at' => now()]);
            }

            return $result;
        });
    }

    public function deleteCustomerService(Customer $customer, Service $service): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $service) {
            // 1. Validar pertencimento
            if ($service->budget->customer_id !== $customer->id) {
                return $this->error('Serviço não pertence ao cliente', OperationStatus::INVALID_DATA);
            }

            // 2. Verificar dependências
            if ($service->invoices()->exists()) {
                return $this->error('Não é possível excluir serviço com faturas vinculadas', OperationStatus::INVALID_DATA);
            }

            // 3. Excluir serviço
            $serviceService = app(ServiceService::class);
            $result = $serviceService->delete($service);

            if ($result->isSuccess()) {
                // 4. Atualizar estatísticas do cliente
                $this->updateCustomerServiceStats($customer);
            }

            return $result;
        });
    }

    private function updateCustomerServiceStats(Customer $customer): void
    {
        $stats = [
            'total_services' => $customer->services()->count(),
            'active_services' => $customer->activeServices()->count(),
            'total_service_value' => $customer->services()->sum('total'),
        ];

        $customer->update($stats);
    }
}
```

### **✅ Integração com Faturas**

```php
class CustomerInvoiceIntegrationService extends AbstractBaseService
{
    public function createInvoiceForCustomer(Customer $customer, InvoiceDTO $dto, User $user): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $dto, $user) {
            // 1. Validar cliente
            if ($customer->status !== 'active') {
                return $this->error('Cliente não está ativo para receber faturas', OperationStatus::INVALID_DATA);
            }

            // 2. Verificar serviço
            $service = Service::find($dto->service_id);
            if (! $service || $service->budget->customer_id !== $customer->id) {
                return $this->error('Serviço não encontrado ou não pertence ao cliente', OperationStatus::INVALID_DATA);
            }

            // 3. Criar fatura
            $invoiceService = app(InvoiceService::class);
            $result = $invoiceService->create($dto, $user);

            if (! $result->isSuccess()) {
                return $result;
            }

            $invoice = $result->getData();

            // 4. Atualizar último contato do cliente
            $customer->update(['last_invoice_at' => now()]);

            // 5. Disparar eventos
            event(new InvoiceCreatedForCustomer($customer, $invoice, $user));

            return $this->success($invoice, 'Fatura criada para cliente');
        });
    }

    public function getCustomerInvoices(Customer $customer, array $filters = []): ServiceResult
    {
        $query = $customer->invoices();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        $invoices = $query->with([
            'service.budget.customer.commonData',
            'service.budget.customer.contact',
            'items.product',
        ])->orderBy('created_at', 'desc')->get();

        return $this->success($invoices, 'Faturas do cliente');
    }

    public function updateCustomerInvoice(Customer $customer, Invoice $invoice, InvoiceDTO $dto): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $invoice, $dto) {
            // 1. Validar pertencimento
            if ($invoice->service->budget->customer_id !== $customer->id) {
                return $this->error('Fatura não pertence ao cliente', OperationStatus::INVALID_DATA);
            }

            // 2. Atualizar fatura
            $invoiceService = app(InvoiceService::class);
            $result = $invoiceService->update($invoice, $dto);

            if ($result->isSuccess()) {
                // 3. Atualizar último contato do cliente
                $customer->update(['last_invoice_at' => now()]);
            }

            return $result;
        });
    }

    public function deleteCustomerInvoice(Customer $customer, Invoice $invoice): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $invoice) {
            // 1. Validar pertencimento
            if ($invoice->service->budget->customer_id !== $customer->id) {
                return $this->error('Fatura não pertence ao cliente', OperationStatus::INVALID_DATA);
            }

            // 2. Verificar status
            if ($invoice->status === 'paid') {
                return $this->error('Não é possível excluir fatura já paga', OperationStatus::INVALID_DATA);
            }

            // 3. Excluir fatura
            $invoiceService = app(InvoiceService::class);
            $result = $invoiceService->delete($invoice);

            if ($result->isSuccess()) {
                // 4. Atualizar estatísticas do cliente
                $this->updateCustomerInvoiceStats($customer);
            }

            return $result;
        });
    }

    public function markInvoiceAsPaid(Customer $customer, Invoice $invoice, array $paymentData): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $invoice, $paymentData) {
            // 1. Validar pertencimento
            if ($invoice->service->budget->customer_id !== $customer->id) {
                return $this->error('Fatura não pertence ao cliente', OperationStatus::INVALID_DATA);
            }

            // 2. Marcar como paga
            $invoiceService = app(InvoiceService::class);
            $result = $invoiceService->markAsPaid($invoice, $paymentData);

            if ($result->isSuccess()) {
                // 3. Atualizar último contato do cliente
                $customer->update(['last_payment_at' => now()]);

                // 4. Disparar eventos
                event(new InvoicePaidForCustomer($customer, $invoice));
            }

            return $result;
        });
    }

    private function updateCustomerInvoiceStats(Customer $customer): void
    {
        $stats = [
            'total_invoices' => $customer->invoices()->count(),
            'pending_invoices' => $customer->pendingInvoices()->count(),
            'paid_invoices' => $customer->paidInvoices()->count(),
            'total_invoice_value' => $customer->invoices()->sum('total'),
            'pending_amount' => $customer->invoices()->where('status', 'pending')->sum('total'),
            'paid_amount' => $customer->invoices()->where('status', 'paid')->sum('total'),
        ];

        $customer->update($stats);
    }
}
```

## 📊 Padrões de Consulta

### **✅ Consultas Integradas**

```php
class CustomerIntegratedQueryService extends AbstractBaseService
{
    public function getCustomerPortfolio(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $portfolio = [
                'customer' => $customer->load([
                    'commonData',
                    'contact',
                    'address',
                    'businessData',
                    'tags',
                ]),
                'financial_summary' => $this->getFinancialSummary($customer),
                'activity_summary' => $this->getActivitySummary($customer),
                'budgets' => $customer->budgets()->with([
                    'services.items.product',
                    'services.invoices',
                ])->orderBy('created_at', 'desc')->get(),
                'services' => $customer->services()->with([
                    'budget',
                    'items.product',
                    'invoices',
                ])->orderBy('created_at', 'desc')->get(),
                'invoices' => $customer->invoices()->with([
                    'service.budget',
                    'items.product',
                ])->orderBy('created_at', 'desc')->get(),
                'interactions' => $customer->interactions()->with('createdBy')
                    ->orderBy('interaction_date', 'desc')->get(),
                'lifecycle_history' => $customer->lifecycleHistory()->with('movedBy')
                    ->orderBy('created_at', 'desc')->get(),
            ];

            return $this->success($portfolio, 'Portfólio completo do cliente');
        });
    }

    public function getCustomerAnalytics(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $analytics = [
                'revenue_trends' => $this->getRevenueTrends($customer),
                'service_patterns' => $this->getServicePatterns($customer),
                'payment_behavior' => $this->getPaymentBehavior($customer),
                'engagement_metrics' => $this->getEngagementMetrics($customer),
                'risk_indicators' => $this->getRiskIndicators($customer),
            ];

            return $this->success($analytics, 'Analytics do cliente');
        });
    }

    private function getFinancialSummary(Customer $customer): array
    {
        return [
            'total_budgets' => $customer->budgets()->count(),
            'total_services' => $customer->services()->count(),
            'total_invoices' => $customer->invoices()->count(),
            'total_revenue' => $customer->invoices()->sum('total'),
            'pending_amount' => $customer->invoices()->where('status', 'pending')->sum('total'),
            'average_invoice_value' => $customer->invoices()->avg('total') ?? 0,
            'payment_ratio' => $this->calculatePaymentRatio($customer),
        ];
    }

    private function getActivitySummary(Customer $customer): array
    {
        return [
            'last_interaction' => $customer->interactions()->latest('interaction_date')->first()?->interaction_date,
            'interaction_frequency' => $this->calculateInteractionFrequency($customer),
            'last_budget_date' => $customer->budgets()->latest('created_at')->first()?->created_at,
            'last_service_date' => $customer->services()->latest('created_at')->first()?->created_at,
            'last_invoice_date' => $customer->invoices()->latest('created_at')->first()?->created_at,
            'current_stage' => $customer->lifecycle_stage,
            'stage_duration' => $customer->stage_changed_at ? now()->diffInDays($customer->stage_changed_at) : 0,
        ];
    }

    private function getRevenueTrends(Customer $customer): array
    {
        return $customer->invoices()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, sum(total) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('revenue', 'month')
            ->toArray();
    }

    private function getServicePatterns(Customer $customer): array
    {
        return $customer->services()
            ->select('service_type', DB::raw('count(*) as count'))
            ->groupBy('service_type')
            ->pluck('count', 'service_type')
            ->toArray();
    }

    private function getPaymentBehavior(Customer $customer): array
    {
        $invoices = $customer->invoices;
        $totalInvoices = $invoices->count();
        $paidInvoices = $invoices->where('status', 'paid')->count();
        $averagePaymentTime = $invoices->where('status', 'paid')->avg(function($invoice) {
            return $invoice->created_at->diffInDays($invoice->transaction_date);
        }) ?? 0;

        return [
            'payment_rate' => $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0,
            'average_payment_time' => $averagePaymentTime,
            'late_payment_count' => $invoices->where('due_date', '<', now())
                ->where('status', 'pending')->count(),
            'payment_methods' => $invoices->groupBy('payment_method')
                ->map->count()->toArray(),
        ];
    }

    private function getEngagementMetrics(Customer $customer): array
    {
        $interactions = $customer->interactions;
        $totalInteractions = $interactions->count();
        $recentInteractions = $interactions->where('interaction_date', '>=', now()->subMonths(3))->count();
        $positiveInteractions = $interactions->whereIn('outcome', ['success', 'partial'])->count();

        return [
            'interaction_frequency' => $totalInteractions > 0 ? $recentInteractions / 3 : 0,
            'engagement_score' => $totalInteractions > 0 ? ($positiveInteractions / $totalInteractions) * 100 : 0,
            'last_contact_days' => $customer->last_interaction_at ? now()->diffInDays($customer->last_interaction_at) : 0,
            'response_rate' => $this->calculateResponseRate($customer),
        ];
    }

    private function getRiskIndicators(Customer $customer): array
    {
        return [
            'overdue_invoices' => $customer->invoices()->where('due_date', '<', now())
                ->where('status', 'pending')->count(),
            'cancelled_budgets' => $customer->budgets()->where('status', 'cancelled')->count(),
            'inactive_days' => $customer->last_interaction_at ? now()->diffInDays($customer->last_interaction_at) : 0,
            'payment_delays' => $customer->invoices()->where('transaction_date', '>', 'due_date')->count(),
            'churn_risk_score' => $this->calculateChurnRiskScore($customer),
        ];
    }

    private function calculatePaymentRatio(Customer $customer): float
    {
        $totalInvoices = $customer->invoices()->count();
        $paidInvoices = $customer->invoices()->where('status', 'paid')->count();

        return $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0.0;
    }

    private function calculateInteractionFrequency(Customer $customer): float
    {
        $firstInteraction = $customer->interactions()->oldest('interaction_date')->first();
        if (! $firstInteraction) {
            return 0.0;
        }

        $daysSinceFirst = $firstInteraction->interaction_date->diffInDays(now());
        $totalInteractions = $customer->interactions()->count();

        return $daysSinceFirst > 0 ? $totalInteractions / $daysSinceFirst : 0.0;
    }

    private function calculateResponseRate(Customer $customer): float
    {
        // Implementar lógica de taxa de resposta baseada em interações
        return 0.0; // Placeholder
    }

    private function calculateChurnRiskScore(Customer $customer): float
    {
        $score = 0;

        // Fatores de risco
        $overdueInvoices = $customer->invoices()->where('due_date', '<', now())
            ->where('status', 'pending')->count();
        $inactiveDays = $customer->last_interaction_at ? now()->diffInDays($customer->last_interaction_at) : 0;
        $cancelledBudgets = $customer->budgets()->where('status', 'cancelled')->count();

        // Cálculo do score (0-100)
        $score += $overdueInvoices * 10;
        $score += min($inactiveDays / 30 * 20, 40); // Máximo 40 pontos para inatividade
        $score += $cancelledBudgets * 5;

        return min($score, 100);
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Integração**

```php
public function testCustomerBudgetIntegration()
{
    $customer = Customer::factory()->create();
    $user = User::factory()->create();

    $dto = new BudgetDTO([
        'customer_id' => $customer->id,
        'description' => 'Test Budget',
        'total_value' => 1000,
    ]);

    $result = $this->budgetIntegrationService->createBudgetForCustomer($customer, $dto, $user);
    $this->assertTrue($result->isSuccess());

    $budget = $result->getData();
    $this->assertEquals($customer->id, $budget->customer_id);
    $this->assertNotNull($customer->fresh()->last_budget_at);
}

public function testCustomerServiceIntegration()
{
    $customer = Customer::factory()->create();
    $budget = Budget::factory()->create(['customer_id' => $customer->id]);
    $user = User::factory()->create();

    $dto = new ServiceDTO([
        'budget_id' => $budget->id,
        'description' => 'Test Service',
        'total' => 500,
    ]);

    $result = $this->serviceIntegrationService->createServiceForCustomer($customer, $dto, $user);
    $this->assertTrue($result->isSuccess());

    $service = $result->getData();
    $this->assertEquals($customer->id, $service->budget->customer_id);
    $this->assertNotNull($customer->fresh()->last_service_at);
}

public function testCustomerInvoiceIntegration()
{
    $customer = Customer::factory()->create();
    $budget = Budget::factory()->create(['customer_id' => $customer->id]);
    $service = Service::factory()->create(['budget_id' => $budget->id]);
    $user = User::factory()->create();

    $dto = new InvoiceDTO([
        'service_id' => $service->id,
        'description' => 'Test Invoice',
        'total' => 500,
    ]);

    $result = $this->invoiceIntegrationService->createInvoiceForCustomer($customer, $dto, $user);
    $this->assertTrue($result->isSuccess());

    $invoice = $result->getData();
    $this->assertEquals($customer->id, $invoice->service->budget->customer_id);
    $this->assertNotNull($customer->fresh()->last_invoice_at);
}

public function testCustomerPortfolio()
{
    $customer = Customer::factory()->create();

    // Criar relacionamentos
    Budget::factory()->count(3)->create(['customer_id' => $customer->id]);
    CustomerInteraction::factory()->count(5)->create(['customer_id' => $customer->id]);

    $result = $this->integratedQueryService->getCustomerPortfolio($customer);
    $this->assertTrue($result->isSuccess());

    $portfolio = $result->getData();
    $this->assertArrayHasKey('customer', $portfolio);
    $this->assertArrayHasKey('financial_summary', $portfolio);
    $this->assertArrayHasKey('budgets', $portfolio);
    $this->assertArrayHasKey('interactions', $portfolio);
}
```

### **✅ Testes de Consultas Integradas**

```php
public function testCustomerAnalytics()
{
    $customer = Customer::factory()->create();

    // Criar faturas para analytics
    Invoice::factory()->count(5)->create([
        'service_id' => Service::factory()->create([
            'budget_id' => Budget::factory()->create(['customer_id' => $customer->id])->id
        ])->id,
        'status' => 'paid',
        'total' => 100,
    ]);

    $result = $this->integratedQueryService->getCustomerAnalytics($customer);
    $this->assertTrue($result->isSuccess());

    $analytics = $result->getData();
    $this->assertArrayHasKey('revenue_trends', $analytics);
    $this->assertArrayHasKey('payment_behavior', $analytics);
    $this->assertArrayHasKey('risk_indicators', $analytics);
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar relacionamentos básicos no Customer model
- [ ] Criar CustomerIntegrationService básico
- [ ] Implementar CustomerBudgetIntegrationService
- [ ] Criar CustomerServiceIntegrationService

### **Fase 2: Core Features**
- [ ] Implementar CustomerInvoiceIntegrationService
- [ ] Criar CustomerIntegratedQueryService
- [ ] Sistema de eventos de integração
- [ ] Validação de integridade referencial

### **Fase 3: Advanced Features**
- [ ] Sistema de cache de consultas integradas
- [ ] Métricas avançadas de integração
- [ ] Dashboard de relacionamento cliente
- [ ] Exportação de dados integrados

### **Fase 4: Integration**
- [ ] Integração com sistemas externos
- [ ] API REST para consultas integradas
- [ ] Webhooks para eventos de integração
- [ ] Sistema de auditoria de integridade

## 📚 Documentação Relacionada

- [Customer Model](../../app/Models/Customer.php)
- [CustomerIntegrationService](../../app/Services/Domain/CustomerIntegrationService.php)
- [CustomerBudgetIntegrationService](../../app/Services/Domain/CustomerBudgetIntegrationService.php)
- [CustomerServiceIntegrationService](../../app/Services/Domain/CustomerServiceIntegrationService.php)
- [CustomerInvoiceIntegrationService](../../app/Services/Domain/CustomerInvoiceIntegrationService.php)
- [CustomerIntegratedQueryService](../../app/Services/Domain/CustomerIntegratedQueryService.php)

## 🎯 Benefícios

### **✅ Integridade de Dados**
- Relacionamentos consistentes entre módulos
- Validação de integridade referencial
- Consistência de informações do cliente
- Evita dados órfãos e duplicados

### **✅ Performance de Consultas**
- Eager loading otimizado
- Consultas integradas eficientes
- Cache de relacionamentos frequentes
- Redução de queries N+1

### **✅ Experiência do Usuário**
- Visão completa do cliente
- Navegação integrada entre módulos
- Informações contextuais
- Fluxos de trabalho simplificados

### **✅ Análise de Negócio**
- Métricas integradas
- Dashboard executivo
- Relatórios completos
- Insights baseados em dados relacionados

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
