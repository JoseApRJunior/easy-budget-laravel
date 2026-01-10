# 🔄 Skill: Customer Lifecycle Management (Ciclo de Vida de Clientes)

**Descrição:** Sistema completo de gestão do ciclo de vida do cliente, desde o lead até a retenção, com automação de fluxos e estratégias de relacionamento.

**Categoria:** CRM e Automação
**Complexidade:** Alta
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Padronizar a gestão completa do ciclo de vida do cliente no Easy Budget, automatizando processos de onboarding, acompanhamento, retenção e reengajamento, com estratégias específicas para cada fase do relacionamento.

## 📋 Requisitos Técnicos

### **✅ Fases do Ciclo de Vida**

```php
enum CustomerLifecycleStage: string
{
    case LEAD = 'lead';                    // Lead/Potencial Cliente
    case PROSPECT = 'prospect';            // Prospect
    case QUALIFIED = 'qualified';          // Lead Qualificado
    case PROPOSAL = 'proposal';            // Em Proposta
    case NEGOTIATION = 'negotiation';      // Em Negociação
    case CLOSED_WON = 'closed_won';        // Fechado Ganho
    case CLOSED_LOST = 'closed_lost';      // Fechado Perdido
    case ACTIVE = 'active';                // Cliente Ativo
    case INACTIVE = 'inactive';            // Cliente Inativo
    case CHURNED = 'churned';              // Cliente Churned
    case REACTIVATED = 'reactivated';      // Cliente Reativado

    public function getNextStages(): array
    {
        return match ($this) {
            self::LEAD => [self::PROSPECT, self::CLOSED_LOST],
            self::PROSPECT => [self::QUALIFIED, self::CLOSED_LOST],
            self::QUALIFIED => [self::PROPOSAL, self::CLOSED_LOST],
            self::PROPOSAL => [self::NEGOTIATION, self::CLOSED_LOST],
            self::NEGOTIATION => [self::CLOSED_WON, self::CLOSED_LOST],
            self::CLOSED_WON => [self::ACTIVE],
            self::ACTIVE => [self::INACTIVE, self::CHURNED],
            self::INACTIVE => [self::REACTIVATED, self::CHURNED],
            self::CHURNED => [self::REACTIVATED],
            self::REACTIVATED => [self::ACTIVE],
            self::CLOSED_LOST => [],
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::CLOSED_WON, self::CLOSED_LOST]);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::LEAD, self::PROSPECT, self::QUALIFIED, self::PROPOSAL, self::NEGOTIATION, self::ACTIVE, self::INACTIVE, self::REACTIVATED]);
    }

    public function isWon(): bool
    {
        return $this === self::CLOSED_WON;
    }

    public function isLost(): bool
    {
        return $this === self::CLOSED_LOST;
    }
}
```

### **✅ Motivos de Perda**

```php
enum LostReason: string
{
    case PRICE = 'price';                  // Preço
    case QUALITY = 'quality';              // Qualidade
    case SERVICE = 'service';              // Serviço
    case TIMING = 'timing';                // Timing
    case COMPETITOR = 'competitor';        // Concorrente
    case BUDGET = 'budget';                // Orçamento
    case NO_NEED = 'no_need';              // Não tem necessidade
    case NO_RESPONSE = 'no_response';      // Sem resposta
    case OTHER = 'other';                  // Outro

    public function getDisplayName(): string
    {
        return match ($this) {
            self::PRICE => 'Preço',
            self::QUALITY => 'Qualidade',
            self::SERVICE => 'Serviço',
            self::TIMING => 'Timing',
            self::COMPETITOR => 'Concorrente',
            self::BUDGET => 'Orçamento',
            self::NO_NEED => 'Não tem necessidade',
            self::NO_RESPONSE => 'Sem resposta',
            self::OTHER => 'Outro',
        };
    }
}
```

### **✅ Estratégias de Retenção**

```php
enum RetentionStrategy: string
{
    case DISCOUNT = 'discount';            // Desconto
    case UPGRADE = 'upgrade';              // Upgrade de plano
    case BUNDLE = 'bundle';                // Pacote
    case CUSTOMIZATION = 'customization';  // Customização
    case SUPPORT = 'support';              // Suporte premium
    case TRAINING = 'training';            // Treinamento
    case CONSULTING = 'consulting';        // Consultoria
    case OTHER = 'other';                  // Outro

    public function getDisplayName(): string
    {
        return match ($this) {
            self::DISCOUNT => 'Desconto',
            self::UPGRADE => 'Upgrade de plano',
            self::BUNDLE => 'Pacote',
            self::CUSTOMIZATION => 'Customização',
            self::SUPPORT => 'Suporte premium',
            self::TRAINING => 'Treinamento',
            self::CONSULTING => 'Consultoria',
            self::OTHER => 'Outro',
        };
    }
}
```

## 🏗️ Estrutura do Ciclo de Vida

### **📊 Modelo de Histórico de Ciclo de Vida**

```php
class CustomerLifecycleHistory extends Model
{
    protected $fillable = [
        'customer_id',
        'tenant_id',
        'from_stage',
        'to_stage',
        'reason',
        'notes',
        'moved_by',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    public function getStageLabelAttribute(): string
    {
        return match ($this->to_stage) {
            'lead' => 'Lead',
            'prospect' => 'Prospect',
            'qualified' => 'Qualificado',
            'proposal' => 'Proposta',
            'negotiation' => 'Negociação',
            'closed_won' => 'Fechado Ganho',
            'closed_lost' => 'Fechado Perdido',
            'active' => 'Ativo',
            'inactive' => 'Inativo',
            'churned' => 'Churned',
            'reactivated' => 'Reativado',
            default => 'Desconhecido',
        };
    }
}
```

### **📝 DTO de Transição de Ciclo de Vida**

```php
readonly class CustomerLifecycleTransitionDTO extends AbstractDTO
{
    public function __construct(
        public string $from_stage,
        public string $to_stage,
        public ?string $reason = null,
        public ?string $notes = null,
        public ?array $metadata = [],
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            from_stage: $data['from_stage'],
            to_stage: $data['to_stage'],
            reason: $data['reason'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
```

## 📋 Gestão do Ciclo de Vida

### **✅ Transição de Fases**

```php
class CustomerLifecycleService extends AbstractBaseService
{
    public function transitionStage(Customer $customer, CustomerLifecycleTransitionDTO $dto, User $user): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $dto, $user) {
            // 1. Validar transição
            $validation = $this->validateTransition($customer, $dto);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Salvar histórico
            $historyData = [
                'customer_id' => $customer->id,
                'tenant_id' => $customer->tenant_id,
                'from_stage' => $customer->lifecycle_stage,
                'to_stage' => $dto->to_stage,
                'reason' => $dto->reason,
                'notes' => $dto->notes,
                'moved_by' => $user->id,
                'metadata' => $dto->metadata,
            ];

            $historyResult = $this->historyRepository->create($historyData);
            if (!$historyResult->isSuccess()) {
                return $historyResult;
            }

            // 3. Atualizar estágio do cliente
            $customer->update([
                'lifecycle_stage' => $dto->to_stage,
                'stage_changed_at' => now(),
                'stage_changed_by' => $user->id,
            ]);

            // 4. Disparar eventos específicos
            $this->triggerLifecycleEvents($customer, $dto, $user);

            // 5. Executar ações automatizadas
            $this->executeAutomatedActions($customer, $dto);

            return $this->success($customer, 'Estágio atualizado com sucesso');
        });
    }

    public function moveLeadToProspect(Customer $customer, User $user): ServiceResult
    {
        $dto = new CustomerLifecycleTransitionDTO(
            from_stage: 'lead',
            to_stage: 'prospect',
            reason: 'qualified',
            notes: 'Lead qualificado através de contato inicial',
        );

        return $this->transitionStage($customer, $dto, $user);
    }

    public function moveProspectToQualified(Customer $customer, User $user): ServiceResult
    {
        $dto = new CustomerLifecycleTransitionDTO(
            from_stage: 'prospect',
            to_stage: 'qualified',
            reason: 'needs_assessment',
            notes: 'Necessidades do cliente avaliadas e validadas',
        );

        return $this->transitionStage($customer, $dto, $user);
    }

    public function moveQualifiedToProposal(Customer $customer, User $user): ServiceResult
    {
        $dto = new CustomerLifecycleTransitionDTO(
            from_stage: 'qualified',
            to_stage: 'proposal',
            reason: 'proposal_sent',
            notes: 'Proposta comercial enviada ao cliente',
        );

        return $this->transitionStage($customer, $dto, $user);
    }

    public function moveProposalToNegotiation(Customer $customer, User $user): ServiceResult
    {
        $dto = new CustomerLifecycleTransitionDTO(
            from_stage: 'proposal',
            to_stage: 'negotiation',
            reason: 'proposal_reviewed',
            notes: 'Proposta revisada e cliente em negociação',
        );

        return $this->transitionStage($customer, $dto, $user);
    }

    public function moveNegotiationToClosedWon(Customer $customer, User $user, string $reason = 'agreement'): ServiceResult
    {
        $dto = new CustomerLifecycleTransitionDTO(
            from_stage: 'negotiation',
            to_stage: 'closed_won',
            reason: $reason,
            notes: 'Negociação concluída com sucesso',
        );

        return $this->transitionStage($customer, $dto, $user);
    }

    public function moveNegotiationToClosedLost(Customer $customer, User $user, LostReason $lostReason, string $notes = ''): ServiceResult
    {
        $dto = new CustomerLifecycleTransitionDTO(
            from_stage: 'negotiation',
            to_stage: 'closed_lost',
            reason: $lostReason->value,
            notes: $notes ?: "Negócio perdido por motivo: {$lostReason->getDisplayName()}",
        );

        return $this->transitionStage($customer, $dto, $user);
    }

    public function moveActiveToInactive(Customer $customer, User $user, string $reason = 'no_activity'): ServiceResult
    {
        $dto = new CustomerLifecycleTransitionDTO(
            from_stage: 'active',
            to_stage: 'inactive',
            reason: $reason,
            notes: 'Cliente inativo por falta de atividade',
        );

        return $this->transitionStage($customer, $dto, $user);
    }

    public function moveInactiveToChurned(Customer $customer, User $user, string $reason = 'no_response'): ServiceResult
    {
        $dto = new CustomerLifecycleTransitionDTO(
            from_stage: 'inactive',
            to_stage: 'churned',
            reason: $reason,
            notes: 'Cliente churned por falta de retorno',
        );

        return $this->transitionStage($customer, $dto, $user);
    }

    public function moveChurnedToReactivated(Customer $customer, User $user, RetentionStrategy $strategy, string $notes = ''): ServiceResult
    {
        $dto = new CustomerLifecycleTransitionDTO(
            from_stage: 'churned',
            to_stage: 'reactivated',
            reason: $strategy->value,
            notes: $notes ?: "Reativado com estratégia: {$strategy->getDisplayName()}",
        );

        return $this->transitionStage($customer, $dto, $user);
    }

    private function validateTransition(Customer $customer, CustomerLifecycleTransitionDTO $dto): ServiceResult
    {
        // Validar estágio atual
        if (! CustomerLifecycleStage::tryFrom($customer->lifecycle_stage)) {
            return $this->error('Estágio atual inválido', OperationStatus::INVALID_DATA);
        }

        // Validar próximo estágio
        if (! CustomerLifecycleStage::tryFrom($dto->to_stage)) {
            return $this->error('Próximo estágio inválido', OperationStatus::INVALID_DATA);
        }

        // Validar transição permitida
        $currentStage = CustomerLifecycleStage::from($customer->lifecycle_stage);
        $allowedNextStages = $currentStage->getNextStages();

        if (! in_array($dto->to_stage, $allowedNextStages)) {
            return $this->error('Transição de estágio não permitida', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Transição válida');
    }

    private function triggerLifecycleEvents(Customer $customer, CustomerLifecycleTransitionDTO $dto, User $user): void
    {
        // Disparar eventos específicos para cada transição
        match ($dto->to_stage) {
            'prospect' => event(new LeadQualified($customer, $user)),
            'qualified' => event(new ProspectQualified($customer, $user)),
            'proposal' => event(new ProposalSent($customer, $user)),
            'negotiation' => event(new NegotiationStarted($customer, $user)),
            'closed_won' => event(new DealWon($customer, $user)),
            'closed_lost' => event(new DealLost($customer, $user, $dto->reason)),
            'inactive' => event(new CustomerInactive($customer, $user)),
            'churned' => event(new CustomerChurned($customer, $user)),
            'reactivated' => event(new CustomerReactivated($customer, $user)),
            default => event(new LifecycleStageChanged($customer, $dto->from_stage, $dto->to_stage, $user)),
        };
    }

    private function executeAutomatedActions(Customer $customer, CustomerLifecycleTransitionDTO $dto): void
    {
        // Executar ações automatizadas baseadas na transição
        match ($dto->to_stage) {
            'prospect' => $this->executeProspectActions($customer),
            'qualified' => $this->executeQualifiedActions($customer),
            'proposal' => $this->executeProposalActions($customer),
            'closed_won' => $this->executeClosedWonActions($customer),
            'closed_lost' => $this->executeClosedLostActions($customer),
            'inactive' => $this->executeInactiveActions($customer),
            'churned' => $this->executeChurnedActions($customer),
            'reactivated' => $this->executeReactivatedActions($customer),
            default => null,
        };
    }

    private function executeProspectActions(Customer $customer): void
    {
        // Enviar e-mail de boas-vindas
        $this->notificationService->sendWelcomeEmail($customer);

        // Agendar follow-up
        $this->followUpService->scheduleFollowUp($customer, 'initial_contact', now()->addDays(3));
    }

    private function executeQualifiedActions(Customer $customer): void
    {
        // Enviar proposta inicial
        $this->proposalService->sendInitialProposal($customer);

        // Agendar reunião de apresentação
        $this->scheduleService->schedulePresentation($customer, now()->addDays(5));
    }

    private function executeProposalActions(Customer $customer): void
    {
        // Agendar follow-up de proposta
        $this->followUpService->scheduleFollowUp($customer, 'proposal_follow_up', now()->addDays(7));
    }

    private function executeClosedWonActions(Customer $customer): void
    {
        // Enviar contrato
        $this->contractService->sendContract($customer);

        // Iniciar processo de onboarding
        $this->onboardingService->startOnboarding($customer);

        // Agendar treinamento
        $this->trainingService->scheduleTraining($customer);
    }

    private function executeClosedLostActions(Customer $customer): void
    {
        // Enviar pesquisa de satisfação
        $this->surveyService->sendLostCustomerSurvey($customer);

        // Agendar follow-up de retenção
        $this->retentionService->scheduleRetentionFollowUp($customer);
    }

    private function executeInactiveActions(Customer $customer): void
    {
        // Enviar e-mail de reengajamento
        $this->engagementService->sendReengagementEmail($customer);

        // Agendar contato de retenção
        $this->retentionService->scheduleRetentionContact($customer);
    }

    private function executeChurnedActions(Customer $customer): void
    {
        // Enviar pesquisa de churn
        $this->surveyService->sendChurnSurvey($customer);

        // Adicionar a lista de retenção
        $this->retentionService->addToRetentionList($customer);
    }

    private function executeReactivatedActions(Customer $customer): void
    {
        // Enviar e-mail de boas-vindas de volta
        $this->notificationService->sendWelcomeBackEmail($customer);

        // Iniciar processo de reonboarding
        $this->onboardingService->startReonboarding($customer);
    }
}
```

### **✅ Automação de Ciclo de Vida**

```php
class CustomerLifecycleAutomationService extends AbstractBaseService
{
    public function runLifecycleAutomation(): ServiceResult
    {
        return $this->safeExecute(function() {
            $actions = [];

            // 1. Identificar leads inativos
            $inactiveLeads = $this->identifyInactiveLeads();
            foreach ($inactiveLeads as $lead) {
                $actions[] = $this->handleInactiveLead($lead);
            }

            // 2. Identificar prospects sem follow-up
            $staleProspects = $this->identifyStaleProspects();
            foreach ($staleProspects as $prospect) {
                $actions[] = $this->handleStaleProspect($prospect);
            }

            // 3. Identificar clientes inativos
            $inactiveCustomers = $this->identifyInactiveCustomers();
            foreach ($inactiveCustomers as $customer) {
                $actions[] = $this->handleInactiveCustomer($customer);
            }

            // 4. Identificar clientes em risco de churn
            $atRiskCustomers = $this->identifyAtRiskCustomers();
            foreach ($atRiskCustomers as $customer) {
                $actions[] = $this->handleAtRiskCustomer($customer);
            }

            return $this->success($actions, 'Automação de ciclo de vida executada');
        });
    }

    private function identifyInactiveLeads(): Collection
    {
        return Customer::where('lifecycle_stage', 'lead')
            ->where('created_at', '<', now()->subDays(7))
            ->whereDoesntHave('interactions', function($query) {
                $query->where('interaction_date', '>=', now()->subDays(7));
            })
            ->get();
    }

    private function identifyStaleProspects(): Collection
    {
        return Customer::where('lifecycle_stage', 'prospect')
            ->where('stage_changed_at', '<', now()->subDays(14))
            ->whereDoesntHave('interactions', function($query) {
                $query->where('interaction_date', '>=', now()->subDays(7));
            })
            ->get();
    }

    private function identifyInactiveCustomers(): Collection
    {
        return Customer::where('lifecycle_stage', 'active')
            ->where('last_interaction_at', '<', now()->subDays(30))
            ->whereDoesntHave('budgets', function($query) {
                $query->where('created_at', '>=', now()->subMonths(3));
            })
            ->get();
    }

    private function identifyAtRiskCustomers(): Collection
    {
        return Customer::where('lifecycle_stage', 'active')
            ->where('last_interaction_at', '<', now()->subDays(15))
            ->whereHas('budgets', function($query) {
                $query->where('status', 'cancelled')
                    ->where('created_at', '>=', now()->subMonth());
            })
            ->get();
    }

    private function handleInactiveLead(Customer $lead): array
    {
        // Enviar e-mail de nutrição
        $this->nurtureService->sendNurtureEmail($lead);

        // Agendar follow-up
        $this->followUpService->scheduleFollowUp($lead, 'lead_nurture', now()->addDays(3));

        return [
            'customer_id' => $lead->id,
            'action' => 'lead_nurtured',
            'message' => 'Lead inativo nutrido',
        ];
    }

    private function handleStaleProspect(Customer $prospect): array
    {
        // Enviar proposta de valor
        $this->proposalService->sendValueProposal($prospect);

        // Agendar contato direto
        $this->contactService->scheduleDirectContact($prospect);

        return [
            'customer_id' => $prospect->id,
            'action' => 'prospect_reengaged',
            'message' => 'Prospect reengajado',
        ];
    }

    private function handleInactiveCustomer(Customer $customer): array
    {
        // Enviar oferta de retenção
        $this->retentionService->sendRetentionOffer($customer);

        // Agendar contato de retenção
        $this->retentionService->scheduleRetentionContact($customer);

        return [
            'customer_id' => $customer->id,
            'action' => 'customer_retained',
            'message' => 'Cliente inativo retido',
        ];
    }

    private function handleAtRiskCustomer(Customer $customer): array
    {
        // Enviar pesquisa de satisfação
        $this->surveyService->sendSatisfactionSurvey($customer);

        // Oferecer suporte premium
        $this->supportService->offerPremiumSupport($customer);

        // Agendar reunião de relacionamento
        $this->relationshipService->scheduleRelationshipMeeting($customer);

        return [
            'customer_id' => $customer->id,
            'action' => 'customer_saved',
            'message' => 'Cliente em risco salvo',
        ];
    }
}
```

### **✅ Métricas de Ciclo de Vida**

```php
class CustomerLifecycleMetricsService extends AbstractBaseService
{
    public function getLifecycleMetrics(array $filters = []): array
    {
        $query = Customer::query();

        // Aplicar filtros
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $customers = $query->get();

        return [
            'total_customers' => $customers->count(),
            'by_stage' => $this->getCustomersByStage($customers),
            'conversion_rates' => $this->getConversionRates($customers),
            'average_cycle_time' => $this->getAverageCycleTime($customers),
            'churn_rate' => $this->getChurnRate($customers),
            'retention_rate' => $this->getRetentionRate($customers),
            'lifecycle_value' => $this->getLifecycleValue($customers),
        ];
    }

    private function getCustomersByStage(Collection $customers): array
    {
        return $customers->groupBy('lifecycle_stage')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    private function getConversionRates(Collection $customers): array
    {
        $totalLeads = $customers->where('lifecycle_stage', 'lead')->count();
        $totalProspects = $customers->where('lifecycle_stage', 'prospect')->count();
        $totalQualified = $customers->where('lifecycle_stage', 'qualified')->count();
        $totalProposals = $customers->where('lifecycle_stage', 'proposal')->count();
        $totalNegotiations = $customers->where('lifecycle_stage', 'negotiation')->count();
        $totalClosedWon = $customers->where('lifecycle_stage', 'closed_won')->count();

        return [
            'lead_to_prospect' => $totalLeads > 0 ? ($totalProspects / $totalLeads) * 100 : 0,
            'prospect_to_qualified' => $totalProspects > 0 ? ($totalQualified / $totalProspects) * 100 : 0,
            'qualified_to_proposal' => $totalQualified > 0 ? ($totalProposals / $totalQualified) * 100 : 0,
            'proposal_to_negotiation' => $totalProposals > 0 ? ($totalNegotiations / $totalProposals) * 100 : 0,
            'negotiation_to_closed_won' => $totalNegotiations > 0 ? ($totalClosedWon / $totalNegotiations) * 100 : 0,
            'overall_conversion' => $totalLeads > 0 ? ($totalClosedWon / $totalLeads) * 100 : 0,
        ];
    }

    private function getAverageCycleTime(Collection $customers): array
    {
        $cycleTimes = [];

        foreach ($customers as $customer) {
            $history = $customer->lifecycleHistory()->orderBy('created_at')->get();

            if ($history->count() >= 2) {
                $firstStage = $history->first();
                $lastStage = $history->last();

                $cycleTime = $firstStage->created_at->diffInDays($lastStage->created_at);
                $cycleTimes[] = $cycleTime;
            }
        }

        return [
            'average_days' => count($cycleTimes) > 0 ? array_sum($cycleTimes) / count($cycleTimes) : 0,
            'median_days' => $this->calculateMedian($cycleTimes),
            'min_days' => count($cycleTimes) > 0 ? min($cycleTimes) : 0,
            'max_days' => count($cycleTimes) > 0 ? max($cycleTimes) : 0,
        ];
    }

    private function getChurnRate(Collection $customers): float
    {
        $totalCustomers = $customers->count();
        $churnedCustomers = $customers->where('lifecycle_stage', 'churned')->count();

        return $totalCustomers > 0 ? ($churnedCustomers / $totalCustomers) * 100 : 0.0;
    }

    private function getRetentionRate(Collection $customers): float
    {
        $totalCustomers = $customers->count();
        $activeCustomers = $customers->where('lifecycle_stage', 'active')->count();

        return $totalCustomers > 0 ? ($activeCustomers / $totalCustomers) * 100 : 0.0;
    }

    private function getLifecycleValue(Collection $customers): float
    {
        $totalValue = 0;
        $customerCount = 0;

        foreach ($customers as $customer) {
            $customerValue = $customer->invoices()->sum('total');
            if ($customerValue > 0) {
                $totalValue += $customerValue;
                $customerCount++;
            }
        }

        return $customerCount > 0 ? $totalValue / $customerCount : 0.0;
    }

    private function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        sort($values);
        $count = count($values);
        $middle = floor(($count - 1) / 2);

        if ($count % 2) {
            return $values[$middle];
        } else {
            return ($values[$middle] + $values[$middle + 1]) / 2;
        }
    }
}
```

## 📊 Dashboard de Ciclo de Vida

### **✅ Dashboard Executivo**

```php
class CustomerLifecycleDashboardService extends AbstractBaseService
{
    public function getLifecycleDashboard(int $tenantId): array
    {
        return [
            'funnel_metrics' => $this->getFunnelMetrics($tenantId),
            'conversion_trends' => $this->getConversionTrends($tenantId),
            'churn_analysis' => $this->getChurnAnalysis($tenantId),
            'retention_insights' => $this->getRetentionInsights($tenantId),
            'revenue_impact' => $this->getRevenueImpact($tenantId),
        ];
    }

    private function getFunnelMetrics(int $tenantId): array
    {
        $stages = CustomerLifecycleStage::cases();
        $metrics = [];

        foreach ($stages as $stage) {
            $count = Customer::where('tenant_id', $tenantId)
                ->where('lifecycle_stage', $stage->value)
                ->count();

            $metrics[$stage->value] = [
                'count' => $count,
                'label' => $stage->name,
                'percentage' => 0, // Será calculado no frontend
            ];
        }

        return $metrics;
    }

    private function getConversionTrends(int $tenantId): array
    {
        return CustomerLifecycleHistory::whereHas('customer', function($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })
        ->selectRaw('DATE(created_at) as date, to_stage, count(*) as count')
        ->groupBy('date', 'to_stage')
        ->orderBy('date')
        ->get()
        ->groupBy('date')
        ->map(function($dayData) {
            return $dayData->pluck('count', 'to_stage')->toArray();
        })
        ->toArray();
    }

    private function getChurnAnalysis(int $tenantId): array
    {
        $churnedCustomers = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'churned')
            ->with('lifecycleHistory')
            ->get();

        $reasons = $churnedCustomers->flatMap(function($customer) {
            return $customer->lifecycleHistory->where('to_stage', 'churned')->pluck('reason');
        })->groupBy(function($reason) {
            return $reason ?: 'unknown';
        })->map->count()->toArray();

        return [
            'total_churned' => $churnedCustomers->count(),
            'by_reason' => $reasons,
            'average_lifecycle_days' => $this->calculateAverageChurnLifecycle($churnedCustomers),
            'revenue_lost' => $churnedCustomers->sum(function($customer) {
                return $customer->invoices->sum('total');
            }),
        ];
    }

    private function getRetentionInsights(int $tenantId): array
    {
        $reactivatedCustomers = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'reactivated')
            ->get();

        return [
            'total_reactivated' => $reactivatedCustomers->count(),
            'reactivation_rate' => $this->calculateReactivationRate($tenantId),
            'average_reactivation_time' => $this->calculateAverageReactivationTime($reactivatedCustomers),
            'retention_strategies' => $this->getRetentionStrategiesUsed($reactivatedCustomers),
        ];
    }

    private function getRevenueImpact(int $tenantId): array
    {
        $customers = Customer::where('tenant_id', $tenantId)->get();

        return [
            'total_revenue' => $customers->sum(function($customer) {
                return $customer->invoices->sum('total');
            }),
            'revenue_by_stage' => $this->getRevenueByStage($customers),
            'lifecycle_roi' => $this->calculateLifecycleROI($customers),
            'customer_acquisition_cost' => $this->calculateCAC($customers),
        ];
    }

    private function calculateAverageChurnLifecycle(Collection $churnedCustomers): float
    {
        $lifetimes = [];

        foreach ($churnedCustomers as $customer) {
            $firstStage = $customer->lifecycleHistory()->oldest()->first();
            $churnStage = $customer->lifecycleHistory()->where('to_stage', 'churned')->latest()->first();

            if ($firstStage && $churnStage) {
                $lifetime = $firstStage->created_at->diffInDays($churnStage->created_at);
                $lifetimes[] = $lifetime;
            }
        }

        return count($lifetimes) > 0 ? array_sum($lifetimes) / count($lifetimes) : 0.0;
    }

    private function calculateReactivationRate(int $tenantId): float
    {
        $totalChurned = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'churned')
            ->count();

        $totalReactivated = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'reactivated')
            ->count();

        return $totalChurned > 0 ? ($totalReactivated / $totalChurned) * 100 : 0.0;
    }

    private function calculateAverageReactivationTime(Collection $reactivatedCustomers): float
    {
        $reactivationTimes = [];

        foreach ($reactivatedCustomers as $customer) {
            $churnStage = $customer->lifecycleHistory()->where('to_stage', 'churned')->latest()->first();
            $reactivationStage = $customer->lifecycleHistory()->where('to_stage', 'reactivated')->latest()->first();

            if ($churnStage && $reactivationStage) {
                $reactivationTime = $churnStage->created_at->diffInDays($reactivationStage->created_at);
                $reactivationTimes[] = $reactivationTime;
            }
        }

        return count($reactivationTimes) > 0 ? array_sum($reactivationTimes) / count($reactivationTimes) : 0.0;
    }

    private function getRetentionStrategiesUsed(Collection $reactivatedCustomers): array
    {
        return $reactivatedCustomers->flatMap(function($customer) {
            return $customer->lifecycleHistory->where('to_stage', 'reactivated')->pluck('reason');
        })->groupBy(function($strategy) {
            return $strategy ?: 'unknown';
        })->map->count()->toArray();
    }

    private function getRevenueByStage(Collection $customers): array
    {
        return $customers->groupBy('lifecycle_stage')
            ->map(function($stageCustomers) {
                return $stageCustomers->sum(function($customer) {
                    return $customer->invoices->sum('total');
                });
            })
            ->toArray();
    }

    private function calculateLifecycleROI(Collection $customers): float
    {
        $totalRevenue = $customers->sum(function($customer) {
            return $customer->invoices->sum('total');
        });

        $totalCost = $customers->count() * 100; // Custo médio de aquisição

        return $totalCost > 0 ? (($totalRevenue - $totalCost) / $totalCost) * 100 : 0.0;
    }

    private function calculateCAC(Collection $customers): float
    {
        $totalMarketingCost = 50000; // Exemplo de custo de marketing
        $totalAcquiredCustomers = $customers->where('lifecycle_stage', '!=', 'lead')->count();

        return $totalAcquiredCustomers > 0 ? $totalMarketingCost / $totalAcquiredCustomers : 0.0;
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Ciclo de Vida**

```php
public function testLifecycleTransition()
{
    $customer = Customer::factory()->create(['lifecycle_stage' => 'lead']);
    $user = User::factory()->create();

    $dto = new CustomerLifecycleTransitionDTO([
        'from_stage' => 'lead',
        'to_stage' => 'prospect',
        'reason' => 'qualified',
        'notes' => 'Lead qualificado',
    ]);

    $result = $this->lifecycleService->transitionStage($customer, $dto, $user);
    $this->assertTrue($result->isSuccess());

    $this->assertEquals('prospect', $customer->fresh()->lifecycle_stage);
}

public function testLeadToProspectTransition()
{
    $customer = Customer::factory()->create(['lifecycle_stage' => 'lead']);
    $user = User::factory()->create();

    $result = $this->lifecycleService->moveLeadToProspect($customer, $user);
    $this->assertTrue($result->isSuccess());

    $this->assertEquals('prospect', $customer->fresh()->lifecycle_stage);
}

public function testInvalidTransition()
{
    $customer = Customer::factory()->create(['lifecycle_stage' => 'lead']);
    $user = User::factory()->create();

    $dto = new CustomerLifecycleTransitionDTO([
        'from_stage' => 'lead',
        'to_stage' => 'closed_won', // Transição inválida
        'reason' => 'test',
    ]);

    $result = $this->lifecycleService->transitionStage($customer, $dto, $user);
    $this->assertFalse($result->isSuccess());
    $this->assertEquals('Transição de estágio não permitida', $result->getMessage());
}

public function testLifecycleAutomation()
{
    // Criar lead inativo
    $inactiveLead = Customer::factory()->create([
        'lifecycle_stage' => 'lead',
        'created_at' => now()->subDays(10),
    ]);

    $result = $this->automationService->runLifecycleAutomation();
    $this->assertTrue($result->isSuccess());

    // Verificar se o lead foi nutrido
    $this->assertTrue($inactiveLead->fresh()->lifecycle_stage === 'lead'); // Não deve mudar automaticamente
}
```

### **✅ Testes de Métricas**

```php
public function testLifecycleMetrics()
{
    $tenant = Tenant::factory()->create();

    // Criar clientes em diferentes estágios
    Customer::factory()->count(10)->create(['tenant_id' => $tenant->id, 'lifecycle_stage' => 'lead']);
    Customer::factory()->count(5)->create(['tenant_id' => $tenant->id, 'lifecycle_stage' => 'prospect']);
    Customer::factory()->count(3)->create(['tenant_id' => $tenant->id, 'lifecycle_stage' => 'closed_won']);

    $metrics = $this->metricsService->getLifecycleMetrics([
        'tenant_id' => $tenant->id,
    ]);

    $this->assertArrayHasKey('total_customers', $metrics);
    $this->assertArrayHasKey('by_stage', $metrics);
    $this->assertArrayHasKey('conversion_rates', $metrics);
}

public function testDashboardData()
{
    $tenant = Tenant::factory()->create();

    $dashboard = $this->dashboardService->getLifecycleDashboard($tenant->id);

    $this->assertArrayHasKey('funnel_metrics', $dashboard);
    $this->assertArrayHasKey('conversion_trends', $dashboard);
    $this->assertArrayHasKey('churn_analysis', $dashboard);
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar CustomerLifecycleHistory model
- [ ] Criar CustomerLifecycleTransitionDTO
- [ ] Implementar CustomerLifecycleService básico
- [ ] Definir enums de estágios e estratégias

### **Fase 2: Core Features**
- [ ] Implementar CustomerLifecycleAutomationService
- [ ] Criar CustomerLifecycleMetricsService
- [ ] Implementar CustomerLifecycleDashboardService
- [ ] Sistema de eventos de ciclo de vida

### **Fase 3: Advanced Features**
- [ ] Automação inteligente baseada em IA
- [ ] Previsão de churn
- [ ] Estratégias de retenção personalizadas
- [ ] Integração com CRM externos

### **Fase 4: Integration**
- [ ] Machine learning para otimização de conversão
- [ ] Sistema de recomendação de estratégias
- [ ] Dashboard executivo avançado
- [ ] Integração com ferramentas de marketing

## 📚 Documentação Relacionada

- [CustomerLifecycleHistory Model](../../app/Models/CustomerLifecycleHistory.php)
- [CustomerLifecycleTransitionDTO](../../app/DTOs/Customer/CustomerLifecycleTransitionDTO.php)
- [CustomerLifecycleService](../../app/Services/Domain/CustomerLifecycleService.php)
- [CustomerLifecycleAutomationService](../../app/Services/Domain/CustomerLifecycleAutomationService.php)
- [CustomerLifecycleMetricsService](../../app/Services/Domain/CustomerLifecycleMetricsService.php)

## 🎯 Benefícios

### **✅ Gestão de Vendas**
- Funil de vendas automatizado
- Conversões otimizadas por fase
- Identificação de gargalos no processo
- Métricas de performance de vendas

### **✅ Retenção de Clientes**
- Identificação precoce de churn
- Estratégias de retenção automatizadas
- Reengajamento inteligente
- Análise de causas de perda

### **✅ Experiência do Cliente**
- Onboarding personalizado
- Comunicação segmentada por fase
- Suporte proativo
- Jornada de cliente otimizada

### **✅ Decisão de Negócio**
- Dashboard executivo completo
- Análise de ROI por fase
- Previsão de receita
- Otimização de estratégias

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
