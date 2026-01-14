# 📋 Skill: Customer Interaction Management (Gestão de Interações)

**Descrição:** Sistema completo de gestão de interações, histórico e relacionamento com clientes.

**Categoria:** CRM e Relacionamento
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Padronizar a gestão de interações com clientes no Easy Budget, permitindo o acompanhamento de histórico de contatos, agendamento de follow-ups, classificação de interações e geração de insights sobre o relacionamento com clientes.

## 📋 Requisitos Técnicos

### **✅ Tipos de Interações**

```php
enum InteractionType: string
{
    case CALL = 'call';                    // Ligação
    case EMAIL = 'email';                  // E-mail
    case MEETING = 'meeting';              // Reunião
    case VISIT = 'visit';                  // Visita
    case PROPOSAL = 'proposal';            // Proposta
    case FOLLOW_UP = 'follow_up';          // Follow-up
    case COMPLAINT = 'complaint';          // Reclamação
    case COMPLIMENT = 'compliment';        // Elogio
    case PAYMENT = 'payment';              // Pagamento
    case INVOICE = 'invoice';              // Fatura
    case SUPPORT = 'support';              // Suporte
    case QUOTE = 'quote';                  // Orçamento
    case CONTRACT = 'contract';            // Contrato
    case RENEWAL = 'renewal';              // Renovação
    case CANCELLATION = 'cancellation';    // Cancelamento
    case OTHER = 'other';                  // Outro

    public function isCommunication(): bool
    {
        return in_array($this, [self::CALL, self::EMAIL, self::MEETING, self::VISIT]);
    }

    public function isBusiness(): bool
    {
        return in_array($this, [self::PROPOSAL, self::QUOTE, self::CONTRACT, self::RENEWAL]);
    }

    public function isFinancial(): bool
    {
        return in_array($this, [self::PAYMENT, self::INVOICE]);
    }

    public function isSupport(): bool
    {
        return in_array($this, [self::SUPPORT, self::COMPLAINT, self::COMPLIMENT]);
    }
}
```

### **✅ Status de Interações**

```php
enum InteractionStatus: string
{
    case PLANNED = 'planned';      // Planejada
    case IN_PROGRESS = 'in_progress'; // Em andamento
    case COMPLETED = 'completed';   // Concluída
    case CANCELLED = 'cancelled';   // Cancelada
    case PENDING = 'pending';       // Pendente

    public function isActive(): bool
    {
        return in_array($this, [self::PLANNED, self::IN_PROGRESS, self::PENDING]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }
}
```

### **✅ Resultados de Interações**

```php
enum InteractionOutcome: string
{
    case SUCCESS = 'success';          // Sucesso
    case PARTIAL = 'partial';          // Parcial
    case FAILED = 'failed';            // Falhou
    case NO_RESPONSE = 'no_response';  // Sem resposta
    case POSTPONED = 'postponed';      // Adiado
    case CANCELLED = 'cancelled';      // Cancelado
    case NOT_INTERESTED = 'not_interested'; // Não interessado
    case NEEDS_FOLLOW_UP = 'needs_follow_up'; // Precisa de follow-up

    public function isPositive(): bool
    {
        return in_array($this, [self::SUCCESS, self::PARTIAL, self::POSTPONED]);
    }

    public function isNegative(): bool
    {
        return in_array($this, [self::FAILED, self::NO_RESPONSE, self::NOT_INTERESTED]);
    }
}
```

## 🏗️ Estrutura de Interações

### **📊 Modelo de Interação**

```php
class CustomerInteraction extends Model
{
    protected $fillable = [
        'customer_id',
        'tenant_id',
        'interaction_type',
        'description',
        'interaction_date',
        'created_by',
        'status',
        'outcome',
        'next_action',
        'next_action_date',
        'priority',
        'tags',
        'attachments',
        'metadata',
    ];

    protected $casts = [
        'interaction_date' => 'datetime',
        'next_action_date' => 'datetime',
        'tags' => 'array',
        'attachments' => 'array',
        'metadata' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'high' => 'Alta',
            'medium' => 'Média',
            'low' => 'Baixa',
            default => 'Normal',
        };
    }

    public function getOutcomeLabelAttribute(): string
    {
        return match ($this->outcome) {
            'success' => 'Sucesso',
            'partial' => 'Parcial',
            'failed' => 'Falhou',
            'no_response' => 'Sem resposta',
            'postponed' => 'Adiado',
            'cancelled' => 'Cancelado',
            'not_interested' => 'Não interessado',
            'needs_follow_up' => 'Precisa de follow-up',
            default => 'Pendente',
        };
    }
}
```

### **📝 DTO de Interação**

```php
readonly class CustomerInteractionDTO extends AbstractDTO
{
    public function __construct(
        public string $type,
        public string $description,
        public string $interaction_date,
        public ?string $status = 'completed',
        public ?string $outcome = null,
        public ?string $next_action = null,
        public ?string $next_action_date = null,
        public ?string $priority = 'medium',
        public ?array $tags = [],
        public ?array $attachments = [],
        public ?array $metadata = [],
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            type: $data['type'],
            description: $data['description'],
            interaction_date: DateHelper::parseDateTime($data['interaction_date'] ?? now()),
            status: $data['status'] ?? 'completed',
            outcome: $data['outcome'] ?? null,
            next_action: $data['next_action'] ?? null,
            next_action_date: DateHelper::parseDateTime($data['next_action_date'] ?? null),
            priority: $data['priority'] ?? 'medium',
            tags: $data['tags'] ?? [],
            attachments: $data['attachments'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }
}
```

## 📋 Gestão de Interações

### **✅ Criação de Interações**

```php
class CustomerInteractionService extends AbstractBaseService
{
    public function createInteraction(Customer $customer, CustomerInteractionDTO $dto, User $user): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $dto, $user) {
            // 1. Validar interação
            $validation = $this->validateInteraction($dto);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Criar interação
            $interactionData = [
                'customer_id' => $customer->id,
                'tenant_id' => $customer->tenant_id,
                'interaction_type' => $dto->type,
                'description' => $dto->description,
                'interaction_date' => $dto->interaction_date,
                'created_by' => $user->id,
                'status' => $dto->status,
                'outcome' => $dto->outcome,
                'next_action' => $dto->next_action,
                'next_action_date' => $dto->next_action_date,
                'priority' => $dto->priority,
                'tags' => $dto->tags,
                'attachments' => $dto->attachments,
                'metadata' => $dto->metadata,
            ];

            $result = $this->repository->create($interactionData);

            if ($result->isSuccess()) {
                $interaction = $result->getData();

                // 3. Disparar eventos
                event(new InteractionCreated($interaction));

                // 4. Atualizar último contato do cliente
                $this->updateCustomerLastContact($customer, $interaction);
            }

            return $result;
        });
    }

    private function validateInteraction(CustomerInteractionDTO $dto): ServiceResult
    {
        // Validar tipo de interação
        if (! InteractionType::tryFrom($dto->type)) {
            return $this->error('Tipo de interação inválido', OperationStatus::INVALID_DATA);
        }

        // Validar data
        if (! DateHelper::isValidDate($dto->interaction_date)) {
            return $this->error('Data de interação inválida', OperationStatus::INVALID_DATA);
        }

        // Validar próxima ação se existir
        if ($dto->next_action && $dto->next_action_date) {
            if ($dto->next_action_date <= $dto->interaction_date) {
                return $this->error('Data da próxima ação deve ser posterior à data da interação', OperationStatus::INVALID_DATA);
            }
        }

        return $this->success(null, 'Interação válida');
    }

    private function updateCustomerLastContact(Customer $customer, CustomerInteraction $interaction): void
    {
        $customer->update([
            'last_interaction_at' => $interaction->interaction_date,
            'last_interaction_type' => $interaction->interaction_type,
        ]);
    }
}
```

### **✅ Agendamento de Follow-ups**

```php
class CustomerFollowUpService extends AbstractBaseService
{
    public function scheduleFollowUp(Customer $customer, FollowUpDTO $dto, User $user): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $dto, $user) {
            // 1. Criar interação de follow-up
            $interactionDTO = new CustomerInteractionDTO(
                type: 'follow_up',
                description: $dto->description,
                interaction_date: $dto->scheduled_date,
                status: 'planned',
                next_action: $dto->next_action,
                next_action_date: $dto->next_action_date,
                priority: $dto->priority,
                tags: ['follow_up', $dto->category],
                metadata: [
                    'follow_up_type' => $dto->type,
                    'category' => $dto->category,
                    'reminder_days' => $dto->reminder_days,
                ],
            );

            return $this->interactionService->createInteraction($customer, $interactionDTO, $user);
        });
    }

    public function getUpcomingFollowUps(int $days = 7): ServiceResult
    {
        $followUps = $this->repository->getUpcomingFollowUps($days);

        return $this->success($followUps, 'Follow-ups próximos');
    }

    public function sendFollowUpReminders(): ServiceResult
    {
        $followUps = $this->repository->getFollowUpsForReminder();

        foreach ($followUps as $followUp) {
            $this->sendFollowUpReminder($followUp);
        }

        return $this->success(null, 'Lembretes enviados');
    }

    private function sendFollowUpReminder(CustomerInteraction $followUp): void
    {
        // Enviar notificação por e-mail
        $this->notificationService->sendFollowUpReminder($followUp);

        // Enviar notificação push se disponível
        $this->pushNotificationService->sendFollowUpReminder($followUp);
    }
}
```

### **✅ Histórico de Interações**

```php
class CustomerHistoryService extends AbstractBaseService
{
    public function getInteractionHistory(Customer $customer, array $filters = []): ServiceResult
    {
        $query = $customer->interactions();

        // Aplicar filtros
        if (isset($filters['type'])) {
            $query->where('interaction_type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('interaction_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('interaction_date', '<=', $filters['date_to']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $interactions = $query->with(['createdBy'])
            ->orderBy('interaction_date', 'desc')
            ->get();

        return $this->success($interactions, 'Histórico de interações');
    }

    public function getInteractionSummary(Customer $customer): array
    {
        $totalInteractions = $customer->interactions()->count();
        $communicationInteractions = $customer->interactions()
            ->whereIn('interaction_type', ['call', 'email', 'meeting', 'visit'])
            ->count();
        $businessInteractions = $customer->interactions()
            ->whereIn('interaction_type', ['proposal', 'quote', 'contract', 'renewal'])
            ->count();
        $pendingInteractions = $customer->interactions()
            ->whereIn('status', ['planned', 'pending'])
            ->count();
        $completedInteractions = $customer->interactions()
            ->where('status', 'completed')
            ->count();

        return [
            'total_interactions' => $totalInteractions,
            'communication_interactions' => $communicationInteractions,
            'business_interactions' => $businessInteractions,
            'pending_interactions' => $pendingInteractions,
            'completed_interactions' => $completedInteractions,
            'interaction_frequency' => $this->calculateInteractionFrequency($customer),
            'last_interaction' => $customer->last_interaction_at,
        ];
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
}
```

## 📊 Análise e Métricas

### **✅ Métricas de Interações**

```php
class CustomerInteractionMetricsService extends AbstractBaseService
{
    public function getInteractionMetrics(array $filters = []): array
    {
        $query = CustomerInteraction::query();

        // Aplicar filtros
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('interaction_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('interaction_date', '<=', $filters['date_to']);
        }

        $interactions = $query->get();

        return [
            'total_interactions' => $interactions->count(),
            'by_type' => $this->getInteractionsByType($interactions),
            'by_status' => $this->getInteractionsByStatus($interactions),
            'by_outcome' => $this->getInteractionsByOutcome($interactions),
            'by_priority' => $this->getInteractionsByPriority($interactions),
            'by_month' => $this->getInteractionsByMonth($interactions),
            'average_response_time' => $this->calculateAverageResponseTime($interactions),
            'conversion_rate' => $this->calculateConversionRate($interactions),
        ];
    }

    private function getInteractionsByType(Collection $interactions): array
    {
        return $interactions->groupBy('interaction_type')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    private function getInteractionsByStatus(Collection $interactions): array
    {
        return $interactions->groupBy('status')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    private function getInteractionsByOutcome(Collection $interactions): array
    {
        return $interactions->groupBy('outcome')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    private function getInteractionsByPriority(Collection $interactions): array
    {
        return $interactions->groupBy('priority')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    private function getInteractionsByMonth(Collection $interactions): array
    {
        return $interactions->groupBy(function($interaction) {
            return $interaction->interaction_date->format('Y-m');
        })->map(fn($group) => $group->count())->toArray();
    }

    private function calculateAverageResponseTime(Collection $interactions): float
    {
        $responseTimes = [];

        foreach ($interactions as $interaction) {
            if ($interaction->next_action_date && $interaction->interaction_date) {
                $responseTime = $interaction->next_action_date->diffInDays($interaction->interaction_date);
                $responseTimes[] = $responseTime;
            }
        }

        return count($responseTimes) > 0 ? array_sum($responseTimes) / count($responseTimes) : 0.0;
    }

    private function calculateConversionRate(Collection $interactions): float
    {
        $totalInteractions = $interactions->count();
        $successfulInteractions = $interactions->where('outcome', 'success')->count();

        return $totalInteractions > 0 ? ($successfulInteractions / $totalInteractions) * 100 : 0.0;
    }
}
```

### **✅ Dashboard de Interações**

```php
class CustomerInteractionDashboardService extends AbstractBaseService
{
    public function getDashboardData(int $tenantId): array
    {
        $today = now();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        return [
            'today_interactions' => $this->getInteractionsCount($tenantId, $today, $today),
            'week_interactions' => $this->getInteractionsCount($tenantId, $weekStart, $today),
            'month_interactions' => $this->getInteractionsCount($tenantId, $monthStart, $today),
            'pending_follow_ups' => $this->getPendingFollowUpsCount($tenantId),
            'overdue_follow_ups' => $this->getOverdueFollowUpsCount($tenantId),
            'top_interaction_types' => $this->getTopInteractionTypes($tenantId),
            'interaction_trend' => $this->getInteractionTrend($tenantId),
            'customer_engagement_score' => $this->calculateCustomerEngagementScore($tenantId),
        ];
    }

    private function getInteractionsCount(int $tenantId, $startDate, $endDate): int
    {
        return CustomerInteraction::where('tenant_id', $tenantId)
            ->whereBetween('interaction_date', [$startDate, $endDate])
            ->count();
    }

    private function getPendingFollowUpsCount(int $tenantId): int
    {
        return CustomerInteraction::where('tenant_id', $tenantId)
            ->where('interaction_type', 'follow_up')
            ->where('status', 'planned')
            ->where('next_action_date', '<=', now()->addDays(7))
            ->count();
    }

    private function getOverdueFollowUpsCount(int $tenantId): int
    {
        return CustomerInteraction::where('tenant_id', $tenantId)
            ->where('interaction_type', 'follow_up')
            ->where('status', 'planned')
            ->where('next_action_date', '<', now())
            ->count();
    }

    private function getTopInteractionTypes(int $tenantId): array
    {
        return CustomerInteraction::where('tenant_id', $tenantId)
            ->groupBy('interaction_type')
            ->selectRaw('interaction_type, count(*) as count')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'interaction_type')
            ->toArray();
    }

    private function getInteractionTrend(int $tenantId): array
    {
        return CustomerInteraction::where('tenant_id', $tenantId)
            ->where('interaction_date', '>=', now()->subMonths(6))
            ->groupBy(DB::raw('DATE_FORMAT(interaction_date, "%Y-%m")'))
            ->selectRaw('DATE_FORMAT(interaction_date, "%Y-%m") as month, count(*) as count')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();
    }

    private function calculateCustomerEngagementScore(int $tenantId): float
    {
        $totalCustomers = Customer::where('tenant_id', $tenantId)->count();
        $activeCustomers = Customer::where('tenant_id', $tenantId)
            ->whereHas('interactions', function($query) {
                $query->where('interaction_date', '>=', now()->subMonths(3));
            })
            ->count();

        return $totalCustomers > 0 ? ($activeCustomers / $totalCustomers) * 100 : 0.0;
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Interações**

```php
public function testCreateInteraction()
{
    $customer = Customer::factory()->create();
    $user = User::factory()->create();

    $dto = new CustomerInteractionDTO([
        'type' => 'call',
        'description' => 'Ligação de follow-up',
        'interaction_date' => now(),
        'next_action' => 'Enviar proposta',
        'next_action_date' => now()->addDays(3),
    ]);

    $result = $this->interactionService->createInteraction($customer, $dto, $user);
    $this->assertTrue($result->isSuccess());

    $interaction = $result->getData();
    $this->assertEquals($customer->id, $interaction->customer_id);
    $this->assertEquals('call', $interaction->interaction_type);
}

public function testScheduleFollowUp()
{
    $customer = Customer::factory()->create();
    $user = User::factory()->create();

    $dto = new FollowUpDTO([
        'description' => 'Follow-up de proposta',
        'scheduled_date' => now()->addDays(7),
        'next_action' => 'Negociar contrato',
        'next_action_date' => now()->addDays(14),
        'priority' => 'high',
        'type' => 'proposal',
        'category' => 'sales',
    ]);

    $result = $this->followUpService->scheduleFollowUp($customer, $dto, $user);
    $this->assertTrue($result->isSuccess());

    $interaction = $result->getData();
    $this->assertEquals('follow_up', $interaction->interaction_type);
    $this->assertEquals('planned', $interaction->status);
}

public function testGetInteractionHistory()
{
    $customer = Customer::factory()->create();

    // Criar interações
    CustomerInteraction::factory()->count(5)->create([
        'customer_id' => $customer->id,
        'interaction_type' => 'call',
    ]);

    $result = $this->historyService->getInteractionHistory($customer);
    $this->assertTrue($result->isSuccess());

    $interactions = $result->getData();
    $this->assertCount(5, $interactions);
}
```

### **✅ Testes de Métricas**

```php
public function testInteractionMetrics()
{
    $tenant = Tenant::factory()->create();

    // Criar interações de diferentes tipos
    CustomerInteraction::factory()->count(10)->create([
        'tenant_id' => $tenant->id,
        'interaction_type' => 'call',
    ]);

    CustomerInteraction::factory()->count(5)->create([
        'tenant_id' => $tenant->id,
        'interaction_type' => 'email',
    ]);

    $metrics = $this->metricsService->getInteractionMetrics([
        'tenant_id' => $tenant->id,
    ]);

    $this->assertEquals(15, $metrics['total_interactions']);
    $this->assertEquals(10, $metrics['by_type']['call']);
    $this->assertEquals(5, $metrics['by_type']['email']);
}

public function testDashboardData()
{
    $tenant = Tenant::factory()->create();

    // Criar interações
    CustomerInteraction::factory()->count(5)->create([
        'tenant_id' => $tenant->id,
        'interaction_date' => now(),
    ]);

    $dashboardData = $this->dashboardService->getDashboardData($tenant->id);

    $this->assertArrayHasKey('today_interactions', $dashboardData);
    $this->assertEquals(5, $dashboardData['today_interactions']);
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar CustomerInteraction model
- [ ] Criar CustomerInteractionDTO
- [ ] Implementar CustomerInteractionService básico
- [ ] Definir enums de tipos e status

### **Fase 2: Core Features**
- [ ] Implementar CustomerFollowUpService
- [ ] Criar CustomerHistoryService
- [ ] Implementar CustomerInteractionMetricsService
- [ ] Criar CustomerInteractionDashboardService

### **Fase 3: Advanced Features**
- [ ] Sistema de notificações e lembretes
- [ ] Integração com calendário
- [ ] Sistema de classificação de clientes
- [ ] Relatórios avançados

### **Fase 4: Integration**
- [ ] Integração com CRM externos
- [ ] Sistema de automação de follow-ups
- [ ] Dashboard executivo
- [ ] Exportação de dados

## 📚 Documentação Relacionada

- [CustomerInteraction Model](../../app/Models/CustomerInteraction.php)
- [CustomerInteractionDTO](../../app/DTOs/Customer/CustomerInteractionDTO.php)
- [CustomerInteractionService](../../app/Services/Domain/CustomerInteractionService.php)
- [CustomerFollowUpService](../../app/Services/Domain/CustomerFollowUpService.php)
- [CustomerHistoryService](../../app/Services/Domain/CustomerHistoryService.php)

## 🎯 Benefícios

### **✅ Gestão de Relacionamento**
- Histórico completo de interações
- Agendamento de follow-ups automatizado
- Classificação e priorização de clientes
- Métricas de engajamento

### **✅ Produtividade**
- Lembretes e notificações automáticas
- Dashboard de acompanhamento
- Relatórios de performance
- Integração com calendário

### **✅ Decisão de Negócio**
- Insights sobre comportamento de clientes
- Identificação de oportunidades
- Análise de efetividade de interações
- Previsão de churn

### **✅ Conformidade**
- Auditoria de todas as interações
- Histórico de decisões
- Documentação de acordos
- Controle de qualidade

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
