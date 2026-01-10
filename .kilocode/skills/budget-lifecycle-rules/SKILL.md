# 💰 Skill: Budget Lifecycle Rules

**Descrição:** Garante o controle correto do ciclo de vida de orçamentos e suas regras de negócio.

**Categoria:** Regras de Negócio
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Implementar e garantir as regras de negócio que controlam o ciclo de vida dos orçamentos no Easy Budget, assegurando que as transições de status sigam fluxos lógicos e que as operações sejam consistentes com o estado atual do orçamento.

## 📋 Requisitos Técnicos

### **✅ Status de Orçamentos**

Implementar enumeração completa de status para orçamentos:

```php
enum BudgetStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::APPROVED]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED, self::EXPIRED, self::CANCELLED]);
    }

    public function canCreateServices(): bool
    {
        return in_array($this, [self::APPROVED]);
    }

    public function canBeEdited(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING]);
    }
}
```

### **✅ Transições de Status Controladas**

```php
class BudgetLifecycleService extends AbstractBaseService
{
    public function changeStatus(Budget $budget, BudgetStatus $newStatus): ServiceResult
    {
        // 1. Validar transição permitida
        if (!$this->isValidTransition($budget->status, $newStatus)) {
            return $this->error(
                'Transição de status não permitida',
                OperationStatus::INVALID_DATA
            );
        }

        // 2. Validar regras de negócio específicas
        if (!$this->validateBusinessRules($budget, $newStatus)) {
            return $this->error(
                'Regras de negócio não atendidas',
                OperationStatus::INVALID_DATA
            );
        }

        // 3. Executar transição
        return $this->repository->update($budget, ['status' => $newStatus->value]);
    }

    private function isValidTransition(BudgetStatus $current, BudgetStatus $new): bool
    {
        $validTransitions = [
            BudgetStatus::DRAFT => [BudgetStatus::PENDING, BudgetStatus::CANCELLED],
            BudgetStatus::PENDING => [BudgetStatus::APPROVED, BudgetStatus::REJECTED, BudgetStatus::EXPIRED],
            BudgetStatus::APPROVED => [BudgetStatus::CANCELLED],
            BudgetStatus::REJECTED => [],
            BudgetStatus::EXPIRED => [],
            BudgetStatus::CANCELLED => []
        ];

        return in_array($new, $validTransitions[$current] ?? []);
    }
}
```

## 🏗️ Regras de Negócio

### **📊 Fluxo Completo de Orçamento**

```
┌─────────────┐    ┌─────────────┐    ┌─────────────────┐
│   DRAFT     │───▶│   PENDING   │───▶│   APPROVED      │
└─────────────┘    └─────────────┘    └─────────────────┘
     │                   │                   │
     │                   │                   │
     ▼                   ▼                   ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────────┐
│  CANCELLED  │    │  REJECTED   │    │   CANCELLED     │
└─────────────┘    └─────────────┘    └─────────────────┘
                              │
                              │
                              ▼
                       ┌─────────────────┐
                       │    EXPIRED      │
                       └─────────────────┘
```

### **📝 Regras de Criação de Serviços**

```php
class BudgetService extends AbstractBaseService
{
    public function canCreateServices(Budget $budget): ServiceResult
    {
        // 1. Validar status do orçamento
        if (! $budget->status->canCreateServices()) {
            return $this->error(
                'Serviços só podem ser criados a partir de orçamentos aprovados',
                OperationStatus::INVALID_DATA
            );
        }

        // 2. Validar data de validade
        if ($budget->due_date && now()->gt($budget->due_date)) {
            return $this->error(
                'Orçamento expirado não pode ter serviços criados',
                OperationStatus::INVALID_DATA
            );
        }

        // 3. Validar se já existem serviços
        $existingServices = $this->serviceRepository->findByBudgetId($budget->id);
        if ($existingServices->count() > 0) {
            return $this->error(
                'Este orçamento já possui serviços associados',
                OperationStatus::INVALID_DATA
            );
        }

        return $this->success(null, 'Orçamento apto para criação de serviços');
    }

    public function createServiceFromBudget(Budget $budget, array $serviceData): ServiceResult
    {
        return $this->safeExecute(function() use ($budget, $serviceData) {
            // 1. Validar regras de criação
            $validation = $this->canCreateServices($budget);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Criar serviço vinculado ao orçamento
            $serviceData = array_merge($serviceData, [
                'budget_id' => $budget->id,
                'customer_id' => $budget->customer_id,
                'total_value' => $budget->total_value,
                'status' => ServiceStatus::PENDING->value,
                'code' => $this->generateServiceCode($budget),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $result = $this->serviceRepository->create($serviceData);

            if ($result->isSuccess()) {
                // 3. Atualizar status do orçamento para IN_PROGRESS
                $this->changeStatus($budget, BudgetStatus::IN_PROGRESS);
            }

            return $result;
        });
    }
}
```

### **📅 Controle de Validade**

```php
class BudgetValidationService extends AbstractBaseService
{
    public function checkExpiration(): void
    {
        $budgets = $this->repository->findExpiringBudgets(now());

        foreach ($budgets as $budget) {
            if (now()->gt($budget->due_date)) {
                $this->changeStatus($budget, BudgetStatus::EXPIRED);

                // Disparar notificação
                $this->sendExpirationNotification($budget);
            }
        }
    }

    public function validateDateRange(array $data): ServiceResult
    {
        $dueDate = $data['due_date'] ?? null;
        $createdAt = $data['created_at'] ?? now();

        if ($dueDate) {
            $dueDate = is_string($dueDate) ? new \DateTime($dueDate) : $dueDate;
            $createdAt = is_string($createdAt) ? new \DateTime($createdAt) : $createdAt;

            if ($dueDate < $createdAt) {
                return $this->error(
                    'Data de validade não pode ser anterior à data de criação',
                    OperationStatus::INVALID_DATA
                );
            }

            // Validar prazo máximo de validade (ex: 90 dias)
            $maxDays = 90;
            $diff = $dueDate->diff($createdAt);
            if ($diff->days > $maxDays) {
                return $this->error(
                    "Prazo de validade não pode exceder {$maxDays} dias",
                    OperationStatus::INVALID_DATA
                );
            }
        }

        return $this->success(null, 'Validação de datas aprovada');
    }
}
```

### **🔄 Integração com Serviços**

```php
class BudgetIntegrationService extends AbstractBaseService
{
    public function syncBudgetStatus(Budget $budget): ServiceResult
    {
        // 1. Obter serviços associados
        $services = $this->serviceRepository->findByBudgetId($budget->id);

        if ($services->isEmpty()) {
            return $this->success(null, 'Orçamento sem serviços associados');
        }

        // 2. Determinar status do orçamento baseado nos serviços
        $budgetStatus = $this->calculateBudgetStatus($services);

        // 3. Atualizar status do orçamento se necessário
        if ($budget->status !== $budgetStatus) {
            return $this->changeStatus($budget, $budgetStatus);
        }

        return $this->success(null, 'Status do orçamento sincronizado');
    }

    private function calculateBudgetStatus(Collection $services): BudgetStatus
    {
        $allCompleted = $services->every(fn($service) => $service->status === ServiceStatus::COMPLETED->value);
        $anyInProgress = $services->contains(fn($service) => $service->status === ServiceStatus::IN_PROGRESS->value);
        $anyCancelled = $services->contains(fn($service) => $service->status === ServiceStatus::CANCELLED->value);

        if ($allCompleted) {
            return BudgetStatus::COMPLETED;
        } elseif ($anyInProgress) {
            return BudgetStatus::IN_PROGRESS;
        } elseif ($anyCancelled) {
            return BudgetStatus::CANCELLED;
        }

        return BudgetStatus::PENDING;
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Transição de Status**

```php
class BudgetLifecycleTest extends TestCase
{
    public function test_valid_status_transitions()
    {
        $budget = Budget::factory()->create(['status' => BudgetStatus::DRAFT->value]);

        // Testar transição válida: DRAFT -> PENDING
        $result = $this->budgetService->changeStatus($budget, BudgetStatus::PENDING);
        $this->assertTrue($result->isSuccess());

        // Testar transição inválida: APPROVED -> PENDING
        $budget->update(['status' => BudgetStatus::APPROVED->value]);
        $result = $this->budgetService->changeStatus($budget, BudgetStatus::PENDING);
        $this->assertFalse($result->isSuccess());
    }

    public function test_service_creation_rules()
    {
        $budget = Budget::factory()->approved()->create();

        // Testar criação de serviço a partir de orçamento aprovado
        $result = $this->budgetService->canCreateServices($budget);
        $this->assertTrue($result->isSuccess());

        // Testar criação de serviço a partir de orçamento expirado
        $budget->update(['status' => BudgetStatus::EXPIRED->value, 'due_date' => now()->subDays(1)]);
        $result = $this->budgetService->canCreateServices($budget);
        $this->assertFalse($result->isSuccess());
    }

    public function test_date_validation()
    {
        $data = [
            'due_date' => now()->subDays(1), // Data passada
            'created_at' => now()
        ];

        $result = $this->budgetValidationService->validateDateRange($data);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Data de validade não pode ser anterior à data de criação', $result->getMessage());
    }
}
```

### **✅ Testes de Integração**

```php
public function test_budget_service_integration()
{
    $budget = Budget::factory()->approved()->create();
    $serviceData = [
        'description' => 'Test service',
        'due_date' => now()->addDays(7)
    ];

    // Testar criação de serviço a partir de orçamento
    $result = $this->budgetService->createServiceFromBudget($budget, $serviceData);
    $this->assertTrue($result->isSuccess());

    // Verificar se o serviço foi criado
    $service = $result->getData();
    $this->assertEquals($budget->id, $service->budget_id);

    // Verificar se o status do orçamento foi atualizado
    $budget->refresh();
    $this->assertEquals(BudgetStatus::IN_PROGRESS->value, $budget->status);
}
```

## 📈 Métricas e Monitoramento

### **✅ Métricas de Performance**

```php
class BudgetMetricsService extends AbstractBaseService
{
    public function getLifecycleMetrics(array $filters = []): array
    {
        $budgets = $this->repository->findWithFilters($filters);

        return [
            'total_budgets' => $budgets->count(),
            'by_status' => $budgets->groupBy('status')->map->count(),
            'conversion_rate' => $this->calculateConversionRate($budgets),
            'average_approval_time' => $this->calculateAverageApprovalTime($budgets),
            'expiration_rate' => $this->calculateExpirationRate($budgets)
        ];
    }

    private function calculateConversionRate(Collection $budgets): float
    {
        $totalPending = $budgets->where('status', BudgetStatus::PENDING->value)->count();
        $totalApproved = $budgets->where('status', BudgetStatus::APPROVED->value)->count();

        return $totalPending > 0 ? ($totalApproved / $totalPending) * 100 : 0;
    }

    private function calculateAverageApprovalTime(Collection $budgets): float
    {
        $approvedBudgets = $budgets->where('status', BudgetStatus::APPROVED->value);

        if ($approvedBudgets->isEmpty()) {
            return 0.0;
        }

        $totalTime = $approvedBudgets->sum(function($budget) {
            return $budget->updated_at->diffInDays($budget->created_at);
        });

        return $totalTime / $approvedBudgets->count();
    }
}
```

### **✅ Alertas e Notificações**

```php
class BudgetAlertService extends AbstractBaseService
{
    public function checkBudgetAlerts(): void
    {
        // 1. Orçamentos próximos da expiração
        $this->checkExpiringBudgets();

        // 2. Orçamentos pendentes por muito tempo
        $this->checkStaleBudgets();

        // 3. Orçamentos aprovados sem serviços
        $this->checkApprovedWithoutServices();
    }

    private function checkExpiringBudgets(): void
    {
        $expiringBudgets = $this->repository->findExpiringBudgets(now()->addDays(3));

        foreach ($expiringBudgets as $budget) {
            $this->sendExpirationAlert($budget);
        }
    }

    private function checkStaleBudgets(): void
    {
        $staleBudgets = $this->repository->findStaleBudgets(now()->subDays(30));

        foreach ($staleBudgets as $budget) {
            $this->sendStaleAlert($budget);
        }
    }
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar BudgetStatus enum
- [ ] Criar BudgetLifecycleService
- [ ] Definir validações de transição

### **Fase 2: Core Features**
- [ ] Implementar regras de criação de serviços
- [ ] Criar controle de validade
- [ ] Implementar integração com serviços

### **Fase 3: Integration**
- [ ] Criar métricas de performance
- [ ] Implementar alertas e notificações
- [ ] Criar dashboard de monitoramento

### **Fase 4: Advanced Features**
- [ ] Integração com calendário
- [ ] Relatórios de conversão
- [ ] Previsões de expiração

## 📚 Documentação Relacionada

- [Budget Model](../../app/Models/Budget.php)
- [BudgetStatus Enum](../../app/Enums/BudgetStatus.php)
- [BudgetLifecycleService](../../app/Services/Domain/BudgetLifecycleService.php)
- [Budget Validation](../../app/Services/Domain/BudgetValidationService.php)

## 🎯 Benefícios

### **✅ Controle Total**
- Visibilidade completa do ciclo de vida dos orçamentos
- Controle de qualidade através de validações
- Histórico detalhado de todas as alterações

### **✅ Integração Perfeita**
- Sincronização automática com serviços
- Fluxo de trabalho integrado
- Dados consistentes entre módulos

### **✅ Gestão de Prazos**
- Controle de validade automático
- Alertas proativos para expirações
- Métricas de performance

### **✅ Tomada de Decisão**
- Dashboards com métricas em tempo real
- Histórico de alterações para auditoria
- Relatórios de eficiência e produtividade

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
