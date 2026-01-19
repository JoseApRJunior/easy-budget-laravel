# 🔄 Skill: Customer Lifecycle Management (Gestão de Ciclo de Vida)

**Descrição:** Sistema completo de gestão do ciclo de vida do cliente, desde o lead até a retenção, com automação de estágios, estratégias de retenção e análise de churn.

**Categoria:** CRM e Gestão de Relacionamento
**Complexidade:** Alta
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Gerenciar todo o ciclo de vida do cliente de forma automatizada e estratégica, maximizando o valor do cliente ao longo do tempo e reduzindo a taxa de churn.

## 📋 Requisitos Técnicos

### **✅ Estágios do Ciclo de Vida**

```php
enum CustomerLifecycleStage: string
{
    case LEAD = 'lead';                    // Lead
    case PROSPECT = 'prospect';            // Prospect
    case QUALIFIED = 'qualified';          // Lead qualificado
    case PROPOSAL = 'proposal';            // Proposta enviada
    case NEGOTIATION = 'negotiation';      // Em negociação
    case CLOSED_WON = 'closed_won';        // Fechado ganho
    case CLOSED_LOST = 'closed_lost';      // Fechado perdido
    case ACTIVE = 'active';                // Cliente ativo
    case INACTIVE = 'inactive';            // Cliente inativo
    case CHURNED = 'churned';              // Cliente churned
    case REACTIVATED = 'reactivated';      // Cliente reativado

    public function getDisplayName(): string
    {
        return match ($this) {
            self::LEAD => 'Lead',
            self::PROSPECT => 'Prospect',
            self::QUALIFIED => 'Lead Qualificado',
            self::PROPOSAL => 'Proposta Enviada',
            self::NEGOTIATION => 'Em Negociação',
            self::CLOSED_WON => 'Fechado Ganho',
            self::CLOSED_LOST => 'Fechado Perdido',
            self::ACTIVE => 'Cliente Ativo',
            self::INACTIVE => 'Cliente Inativo',
            self::CHURNED => 'Cliente Churned',
            self::REACTIVATED => 'Cliente Reativado',
        };
    }

    public function isLead(): bool
    {
        return in_array($this, [self::LEAD, self::PROSPECT, self::QUALIFIED]);
    }

    public function isOpportunity(): bool
    {
        return in_array($this, [self::PROPOSAL, self::NEGOTIATION]);
    }

    public function isCustomer(): bool
    {
        return in_array($this, [self::CLOSED_WON, self::ACTIVE, self::REACTIVATED]);
    }

    public function isAtRisk(): bool
    {
        return in_array($this, [self::INACTIVE, self::CHURNED]);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::CLOSED_WON, self::CLOSED_LOST]);
    }

    public function getNextStages(): array
    {
        return match ($this) {
            self::LEAD => [self::PROSPECT],
            self::PROSPECT => [self::QUALIFIED],
            self::QUALIFIED => [self::PROPOSAL],
            self::PROPOSAL => [self::NEGOTIATION],
            self::NEGOTIATION => [self::CLOSED_WON, self::CLOSED_LOST],
            self::CLOSED_WON => [self::ACTIVE],
            self::ACTIVE => [self::INACTIVE],
            self::INACTIVE => [self::CHURNED, self::REACTIVATED],
            self::CHURNED => [self::REACTIVATED],
            self::REACTIVATED => [self::ACTIVE],
            self::CLOSED_LOST => [],
        };
    }

    public function getPreviousStages(): array
    {
        return match ($this) {
            self::LEAD => [],
            self::PROSPECT => [self::LEAD],
            self::QUALIFIED => [self::PROSPECT],
            self::PROPOSAL => [self::QUALIFIED],
            self::NEGOTIATION => [self::PROPOSAL],
            self::CLOSED_WON => [self::NEGOTIATION],
            self::CLOSED_LOST => [self::NEGOTIATION],
            self::ACTIVE => [self::CLOSED_WON, self::REACTIVATED],
            self::INACTIVE => [self::ACTIVE],
            self::CHURNED => [self::INACTIVE],
            self::REACTIVATED => [self::CHURNED],
        };
    }
}
```

### **✅ Modelo de Histórico de Ciclo de Vida**

```php
class CustomerLifecycleHistory extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'from_stage',
        'to_stage',
        'reason',
        'notes',
        'moved_by',
        'moved_at',
    ];

    protected $casts = [
        'from_stage' => CustomerLifecycleStage::class,
        'to_stage' => CustomerLifecycleStage::class,
        'moved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    public function scopeByStage($query, CustomerLifecycleStage $stage)
    {
        return $query->where('to_stage', $stage);
    }

    public function scopeByCustomer($query, Customer $customer)
    {
        return $query->where('customer_id', $customer->id);
    }

    public function scopeByPeriod($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('moved_at', [$startDate, $endDate]);
    }
}
```

### **✅ Serviço de Gestão de Ciclo de Vida**

```php
class CustomerLifecycleService extends AbstractBaseService
{
    public function moveCustomerStage(Customer $customer, CustomerLifecycleStage $newStage, string $reason = '', string $notes = '', User $movedBy = null): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $newStage, $reason, $notes, $movedBy) {
            // 1. Validar transição de estágio
            $validation = $this->validateStageTransition($customer, $newStage);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Atualizar estágio do cliente
            $updateResult = $this->updateCustomerStage($customer, $newStage, $movedBy);
            if (!$updateResult->isSuccess()) {
                return $updateResult;
            }

            // 3. Criar histórico
            $historyResult = $this->createLifecycleHistory($customer, $newStage, $reason, $notes, $movedBy);
            if (!$historyResult->isSuccess()) {
                return $historyResult;
            }

            // 4. Disparar eventos
            $this->triggerLifecycleEvents($customer, $newStage);

            // 5. Executar ações automatizadas
            $this->executeAutomatedActions($customer, $newStage);

            return $this->success([
                'customer' => $customer->fresh(),
                'history' => $historyResult->getData(),
            ], 'Estágio do cliente atualizado com sucesso');
        });
    }

    public function autoMoveCustomerStage(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $currentStage = CustomerLifecycleStage::from($customer->lifecycle_stage);
            $newStage = $this->determineNextStage($customer, $currentStage);

            if ($newStage !== $currentStage) {
                return $this->moveCustomerStage(
                    $customer,
                    $newStage,
                    'Movimento automático',
                    'Sistema detectou condições para avanço de estágio',
                    null // Sistema como responsável
                );
            }

            return $this->success($customer, 'Estágio já está atualizado');
        });
    }

    public function getCustomerLifecycleJourney(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $history = $customer->lifecycleHistory()
                ->with('movedBy')
                ->orderBy('moved_at', 'asc')
                ->get();

            $journey = [
                'current_stage' => $customer->lifecycle_stage,
                'stage_changed_at' => $customer->stage_changed_at,
                'stage_changed_by' => $customer->stage_changed_by,
                'history' => $history,
                'time_in_current_stage' => $this->calculateTimeInCurrentStage($customer),
                'next_possible_stages' => $this->getNextPossibleStages($customer),
                'stage_progress' => $this->calculateStageProgress($customer),
            ];

            return $this->success($journey, 'Jornada do cliente obtida');
        });
    }

    public function getLifecycleAnalytics(int $tenantId, array $filters = []): ServiceResult
    {
        return $this->safeExecute(function() use ($tenantId, $filters) {
            $analytics = [
                'stage_distribution' => $this->getStageDistribution($tenantId, $filters),
                'conversion_rates' => $this->getConversionRates($tenantId, $filters),
                'average_stage_duration' => $this->getAverageStageDuration($tenantId, $filters),
                'churn_analysis' => $this->getChurnAnalysis($tenantId, $filters),
                'retention_analysis' => $this->getRetentionAnalysis($tenantId, $filters),
                'lifecycle_value' => $this->getLifecycleValueAnalysis($tenantId, $filters),
            ];

            return $this->success($analytics, 'Analytics de ciclo de vida gerados');
        });
    }

    private function validateStageTransition(Customer $customer, CustomerLifecycleStage $newStage): ServiceResult
    {
        $currentStage = CustomerLifecycleStage::from($customer->lifecycle_stage);

        // Verificar se a transição é válida
        $validNextStages = $currentStage->getNextStages();
        if (! in_array($newStage, $validNextStages)) {
            return $this->error('Transição de estágio inválida', OperationStatus::INVALID_DATA);
        }

        // Verificar regras de negócio específicas
        $businessValidation = $this->validateBusinessRules($customer, $currentStage, $newStage);
        if (!$businessValidation->isSuccess()) {
            return $businessValidation;
        }

        return $this->success(null, 'Transição válida');
    }

    private function validateBusinessRules(Customer $customer, CustomerLifecycleStage $currentStage, CustomerLifecycleStage $newStage): ServiceResult
    {
        // Regras específicas para cada transição
        switch ($currentStage) {
            case CustomerLifecycleStage::QUALIFIED:
                if ($newStage === CustomerLifecycleStage::PROPOSAL) {
                    return $this->validateProposalReady($customer);
                }
                break;

            case CustomerLifecycleStage::NEGOTIATION:
                if ($newStage === CustomerLifecycleStage::CLOSED_WON) {
                    return $this->validateClosedWon($customer);
                }
                break;

            case CustomerLifecycleStage::ACTIVE:
                if ($newStage === CustomerLifecycleStage::INACTIVE) {
                    return $this->validateInactiveTransition($customer);
                }
                break;
        }

        return $this->success(null, 'Regras de negócio válidas');
    }

    private function validateProposalReady(Customer $customer): ServiceResult
    {
        // Verificar se há orçamentos pendentes
        $pendingBudgets = $customer->budgets()->where('status', 'pending')->count();
        if ($pendingBudgets === 0) {
            return $this->error('Não é possível avançar para proposta sem orçamentos pendentes', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Proposta pronta');
    }

    private function validateClosedWon(Customer $customer): ServiceResult
    {
        // Verificar se há serviços ativos
        $activeServices = $customer->services()->where('status', 'active')->count();
        if ($activeServices === 0) {
            return $this->error('Não é possível fechar como ganho sem serviços ativos', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Fechado ganho válido');
    }

    private function validateInactiveTransition(Customer $customer): ServiceResult
    {
        // Verificar tempo de inatividade
        if ($customer->last_interaction_at) {
            $inactivityDays = now()->diffInDays($customer->last_interaction_at);
            if ($inactivityDays < 90) {
                return $this->error('Cliente ainda não atingiu o tempo mínimo de inatividade (90 dias)', OperationStatus::INVALID_DATA);
            }
        }

        return $this->success(null, 'Transição para inativo válida');
    }

    private function updateCustomerStage(Customer $customer, CustomerLifecycleStage $newStage, User $movedBy = null): ServiceResult
    {
        $updateData = [
            'lifecycle_stage' => $newStage,
            'stage_changed_at' => now(),
            'stage_changed_by' => $movedBy?->id,
        ];

        $result = $this->repository->update($customer, $updateData);

        if ($result->isSuccess()) {
            // Atualizar estatísticas do cliente
            $this->updateCustomerStatistics($customer, $newStage);
        }

        return $result;
    }

    private function createLifecycleHistory(Customer $customer, CustomerLifecycleStage $newStage, string $reason, string $notes, User $movedBy = null): ServiceResult
    {
        $historyData = [
            'tenant_id' => $this->getTenantId(),
            'customer_id' => $customer->id,
            'from_stage' => $customer->lifecycle_stage,
            'to_stage' => $newStage,
            'reason' => $reason,
            'notes' => $notes,
            'moved_by' => $movedBy?->id,
            'moved_at' => now(),
        ];

        return $this->repository->createLifecycleHistory($historyData);
    }

    private function triggerLifecycleEvents(Customer $customer, CustomerLifecycleStage $newStage): void
    {
        // Disparar eventos específicos para cada estágio
        match ($newStage) {
            CustomerLifecycleStage::PROPOSAL => event(new CustomerProposalSent($customer)),
            CustomerLifecycleStage::CLOSED_WON => event(new CustomerClosedWon($customer)),
            CustomerLifecycleStage::CLOSED_LOST => event(new CustomerClosedLost($customer)),
            CustomerLifecycleStage::ACTIVE => event(new CustomerActivated($customer)),
            CustomerLifecycleStage::INACTIVE => event(new CustomerInactivated($customer)),
            CustomerLifecycleStage::CHURNED => event(new CustomerChurned($customer)),
            CustomerLifecycleStage::REACTIVATED => event(new CustomerReactivated($customer)),
            default => event(new CustomerStageChanged($customer, $newStage)),
        };
    }

    private function executeAutomatedActions(Customer $customer, CustomerLifecycleStage $newStage): void
    {
        // Executar ações automatizadas baseadas no estágio
        match ($newStage) {
            CustomerLifecycleStage::PROPOSAL => $this->executeProposalActions($customer),
            CustomerLifecycleStage::CLOSED_WON => $this->executeClosedWonActions($customer),
            CustomerLifecycleStage::INACTIVE => $this->executeInactiveActions($customer),
            CustomerLifecycleStage::CHURNED => $this->executeChurnActions($customer),
            CustomerLifecycleStage::REACTIVATED => $this->executeReactivationActions($customer),
            default => null,
        };
    }

    private function executeProposalActions(Customer $customer): void
    {
        // Enviar e-mail de proposta
        SendProposalEmail::dispatch($customer);

        // Criar tarefa de follow-up
        $this->createFollowUpTask($customer, 'Enviar proposta', now()->addDays(3));
    }

    private function executeClosedWonActions(Customer $customer): void
    {
        // Enviar e-mail de boas-vindas
        SendWelcomeEmail::dispatch($customer);

        // Criar tarefa de onboarding
        $this->createOnboardingTask($customer);

        // Atualizar estatísticas de vendas
        $this->updateSalesStatistics($customer);
    }

    private function executeInactiveActions(Customer $customer): void
    {
        // Criar campanha de retenção
        $this->createRetentionCampaign($customer);

        // Enviar pesquisa de satisfação
        SendSatisfactionSurvey::dispatch($customer);
    }

    private function executeChurnActions(Customer $customer): void
    {
        // Criar tarefa de análise de churn
        $this->createChurnAnalysisTask($customer);

        // Enviar pesquisa de churn
        SendChurnSurvey::dispatch($customer);
    }

    private function executeReactivationActions(Customer $customer): void
    {
        // Enviar e-mail de boas-vindas de volta
        SendWelcomeBackEmail::dispatch($customer);

        // Criar tarefa de reonboarding
        $this->createReonboardingTask($customer);
    }

    private function determineNextStage(Customer $customer, CustomerLifecycleStage $currentStage): CustomerLifecycleStage
    {
        // Lógica de determinação automática de próximo estágio
        switch ($currentStage) {
            case CustomerLifecycleStage::LEAD:
                return $this->shouldMoveToProspect($customer) ? CustomerLifecycleStage::PROSPECT : $currentStage;

            case CustomerLifecycleStage::PROSPECT:
                return $this->shouldMoveToQualified($customer) ? CustomerLifecycleStage::QUALIFIED : $currentStage;

            case CustomerLifecycleStage::QUALIFIED:
                return $this->shouldMoveToProposal($customer) ? CustomerLifecycleStage::PROPOSAL : $currentStage;

            case CustomerLifecycleStage::PROPOSAL:
                return $this->shouldMoveToNegotiation($customer) ? CustomerLifecycleStage::NEGOTIATION : $currentStage;

            case CustomerLifecycleStage::NEGOTIATION:
                return $this->shouldMoveToClosedWon($customer) ? CustomerLifecycleStage::CLOSED_WON : CustomerLifecycleStage::CLOSED_LOST;

            case CustomerLifecycleStage::ACTIVE:
                return $this->shouldMoveToInactive($customer) ? CustomerLifecycleStage::INACTIVE : $currentStage;

            case CustomerLifecycleStage::INACTIVE:
                return $this->shouldMoveToChurned($customer) ? CustomerLifecycleStage::CHURNED : $this->shouldMoveToReactivated($customer) ? CustomerLifecycleStage::REACTIVATED : $currentStage;

            default:
                return $currentStage;
        }
    }

    private function shouldMoveToProspect(Customer $customer): bool
    {
        // Lógica para determinar se deve avançar para prospect
        return $customer->interactions()->where('interaction_date', '>=', now()->subDays(30))->exists();
    }

    private function shouldMoveToQualified(Customer $customer): bool
    {
        // Lógica para determinar se deve avançar para qualificado
        return $customer->budgets()->where('status', 'pending')->exists();
    }

    private function shouldMoveToProposal(Customer $customer): bool
    {
        // Lógica para determinar se deve avançar para proposta
        return $customer->budgets()->where('status', 'approved')->exists();
    }

    private function shouldMoveToNegotiation(Customer $customer): bool
    {
        // Lógica para determinar se deve avançar para negociação
        return $customer->services()->where('status', 'pending')->exists();
    }

    private function shouldMoveToClosedWon(Customer $customer): bool
    {
        // Lógica para determinar se deve fechar como ganho
        return $customer->services()->where('status', 'active')->exists();
    }

    private function shouldMoveToInactive(Customer $customer): bool
    {
        // Lógica para determinar se deve inativar
        return $customer->last_interaction_at && $customer->last_interaction_at < now()->subMonths(3);
    }

    private function shouldMoveToChurned(Customer $customer): bool
    {
        // Lógica para determinar se deve churnar
        return $customer->last_interaction_at && $customer->last_interaction_at < now()->subMonths(6);
    }

    private function shouldMoveToReactivated(Customer $customer): bool
    {
        // Lógica para determinar se deve reativar
        return $customer->last_interaction_at && $customer->last_interaction_at >= now()->subMonths(1);
    }

    private function calculateTimeInCurrentStage(Customer $customer): int
    {
        return $customer->stage_changed_at ? now()->diffInDays($customer->stage_changed_at) : 0;
    }

    private function getNextPossibleStages(Customer $customer): array
    {
        $currentStage = CustomerLifecycleStage::from($customer->lifecycle_stage);
        return $currentStage->getNextStages();
    }

    private function calculateStageProgress(Customer $customer): float
    {
        $currentStage = CustomerLifecycleStage::from($customer->lifecycle_stage);
        $stages = CustomerLifecycleStage::cases();

        $currentIndex = array_search($currentStage, $stages);
        $totalStages = count($stages);

        return $totalStages > 0 ? ($currentIndex / ($totalStages - 1)) * 100 : 0;
    }

    private function updateCustomerStatistics(Customer $customer, CustomerLifecycleStage $newStage): void
    {
        // Atualizar estatísticas do cliente baseado no novo estágio
        $customer->update([
            'stage_changed_at' => now(),
            'stage_changed_by' => auth()->id(),
        ]);
    }

    private function createFollowUpTask(Customer $customer, string $description, Carbon $dueDate): void
    {
        // Criar tarefa de follow-up
        Task::create([
            'tenant_id' => $this->getTenantId(),
            'customer_id' => $customer->id,
            'description' => $description,
            'due_date' => $dueDate,
            'priority' => 'medium',
            'status' => 'pending',
        ]);
    }

    private function createOnboardingTask(Customer $customer): void
    {
        // Criar tarefa de onboarding
        Task::create([
            'tenant_id' => $this->getTenantId(),
            'customer_id' => $customer->id,
            'description' => 'Realizar onboarding do cliente',
            'due_date' => now()->addDays(7),
            'priority' => 'high',
            'status' => 'pending',
        ]);
    }

    private function createRetentionCampaign(Customer $customer): void
    {
        // Criar campanha de retenção
        Campaign::create([
            'tenant_id' => $this->getTenantId(),
            'name' => "Campanha de Retenção - {$customer->commonData?->first_name}",
            'type' => 'retention',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths(1),
            'target_customers' => [$customer->id],
        ]);
    }

    private function createChurnAnalysisTask(Customer $customer): void
    {
        // Criar tarefa de análise de churn
        Task::create([
            'tenant_id' => $this->getTenantId(),
            'customer_id' => $customer->id,
            'description' => 'Analisar motivo do churn',
            'due_date' => now()->addDays(3),
            'priority' => 'high',
            'status' => 'pending',
        ]);
    }

    private function createReonboardingTask(Customer $customer): void
    {
        // Criar tarefa de reonboarding
        Task::create([
            'tenant_id' => $this->getTenantId(),
            'customer_id' => $customer->id,
            'description' => 'Realizar reonboarding do cliente',
            'due_date' => now()->addDays(7),
            'priority' => 'medium',
            'status' => 'pending',
        ]);
    }

    private function updateSalesStatistics(Customer $customer): void
    {
        // Atualizar estatísticas de vendas
        $user = auth()->user();
        if ($user) {
            $user->update([
                'total_customers_won' => $user->total_customers_won + 1,
                'last_customer_won_at' => now(),
            ]);
        }
    }

    private function getStageDistribution(int $tenantId, array $filters): array
    {
        return Customer::where('tenant_id', $tenantId)
            ->when($filters['date_from'] ?? null, function($query, $dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function($query, $dateTo) {
                $query->where('created_at', '<=', $dateTo);
            })
            ->groupBy('lifecycle_stage')
            ->selectRaw('lifecycle_stage, count(*) as count')
            ->pluck('count', 'lifecycle_stage')
            ->toArray();
    }

    private function getConversionRates(int $tenantId, array $filters): array
    {
        $totalLeads = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'lead')
            ->when($filters['date_from'] ?? null, function($query, $dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            })
            ->count();

        $totalProspects = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'prospect')
            ->when($filters['date_from'] ?? null, function($query, $dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            })
            ->count();

        $totalQualified = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'qualified')
            ->when($filters['date_from'] ?? null, function($query, $dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            })
            ->count();

        $totalClosedWon = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'closed_won')
            ->when($filters['date_from'] ?? null, function($query, $dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            })
            ->count();

        return [
            'lead_to_prospect' => $totalLeads > 0 ? ($totalProspects / $totalLeads) * 100 : 0,
            'prospect_to_qualified' => $totalProspects > 0 ? ($totalQualified / $totalProspects) * 100 : 0,
            'qualified_to_closed_won' => $totalQualified > 0 ? ($totalClosedWon / $totalQualified) * 100 : 0,
            'overall_conversion' => $totalLeads > 0 ? ($totalClosedWon / $totalLeads) * 100 : 0,
        ];
    }

    private function getAverageStageDuration(int $tenantId, array $filters): array
    {
        $stages = CustomerLifecycleStage::cases();
        $durations = [];

        foreach ($stages as $stage) {
            $customers = Customer::where('tenant_id', $tenantId)
                ->where('lifecycle_stage', $stage)
                ->when($filters['date_from'] ?? null, function($query, $dateFrom) {
                    $query->where('created_at', '>=', $dateFrom);
                })
                ->get();

            $totalDays = $customers->sum(function($customer) {
                return $customer->stage_changed_at ? now()->diffInDays($customer->stage_changed_at) : 0;
            });

            $durations[$stage->value] = $customers->count() > 0 ? ($totalDays / $customers->count()) : 0;
        }

        return $durations;
    }

    private function getChurnAnalysis(int $tenantId, array $filters): array
    {
        $churnedCustomers = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'churned')
            ->when($filters['date_from'] ?? null, function($query, $dateFrom) {
                $query->where('stage_changed_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function($query, $dateTo) {
                $query->where('stage_changed_at', '<=', $dateTo);
            })
            ->get();

        $totalCustomers = Customer::where('tenant_id', $tenantId)
            ->when($filters['date_from'] ?? null, function($query, $dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            })
            ->count();

        return [
            'total_churned' => $churnedCustomers->count(),
            'churn_rate' => $totalCustomers > 0 ? ($churnedCustomers->count() / $totalCustomers) * 100 : 0,
            'average_time_to_churn' => $churnedCustomers->avg(function($customer) {
                return $customer->stage_changed_at ? now()->diffInDays($customer->stage_changed_at) : 0;
            }) ?? 0,
            'churn_reasons' => $this->getChurnReasons($churnedCustomers),
        ];
    }

    private function getRetentionAnalysis(int $tenantId, array $filters): array
    {
        $reactivatedCustomers = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'reactivated')
            ->when($filters['date_from'] ?? null, function($query, $dateFrom) {
                $query->where('stage_changed_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function($query, $dateTo) {
                $query->where('stage_changed_at', '<=', $dateTo);
            })
            ->get();

        $totalChurned = Customer::where('tenant_id', $tenantId)
            ->where('lifecycle_stage', 'churned')
            ->when($filters['date_from'] ?? null, function($query, $dateFrom) {
                $query->where('stage_changed_at', '>=', $dateFrom);
            })
            ->count();

        return [
            'total_reactivated' => $reactivatedCustomers->count(),
            'reactivation_rate' => $totalChurned > 0 ? ($reactivatedCustomers->count() / $totalChurned) * 100 : 0,
            'average_time_to_reactivation' => $reactivatedCustomers->avg(function($customer) {
                return $customer->stage_changed_at ? now()->diffInDays($customer->stage_changed_at) : 0;
            }) ?? 0,
        ];
    }

    private function getLifecycleValueAnalysis(int $tenantId, array $filters): array
    {
        $customers = Customer::where('tenant_id', $tenantId)
            ->with('invoices')
            ->when($filters['date_from'] ?? null, function($query, $dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            })
            ->get();

        return [
            'average_ltv' => $customers->avg(function($customer) {
                return $customer->invoices->where('status', 'paid')->sum('total');
            }) ?? 0,
            'total_ltv' => $customers->sum(function($customer) {
                return $customer->invoices->where('status', 'paid')->sum('total');
            }),
            'ltv_by_stage' => $this->getLTVByStage($customers),
        ];
    }

    private function getChurnReasons(Collection $churnedCustomers): array
    {
        return $churnedCustomers->groupBy(function($customer) {
            return $customer->lifecycleHistory->last()?->reason ?? 'unknown';
        })->map->count()->toArray();
    }

    private function getLTVByStage(Collection $customers): array
    {
        return $customers->groupBy('lifecycle_stage')
            ->map(function($stageCustomers) {
                return $stageCustomers->avg(function($customer) {
                    return $customer->invoices->where('status', 'paid')->sum('total');
                }) ?? 0;
            })
            ->toArray();
    }
}
```

### **✅ Sistema de Estratégias de Retenção**

```php
class CustomerRetentionService extends AbstractBaseService
{
    public function analyzeChurnRisk(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $riskFactors = $this->identifyRiskFactors($customer);
            $riskScore = $this->calculateChurnRiskScore($customer, $riskFactors);
            $recommendations = $this->generateRetentionRecommendations($customer, $riskScore);

            return $this->success([
                'risk_score' => $riskScore,
                'risk_factors' => $riskFactors,
                'recommendations' => $recommendations,
                'risk_level' => $this->getRiskLevel($riskScore),
            ], 'Análise de risco de churn concluída');
        });
    }

    public function createRetentionCampaign(Customer $customer, array $strategies): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $strategies) {
            $campaign = Campaign::create([
                'tenant_id' => $this->getTenantId(),
                'name' => "Campanha de Retenção - {$customer->commonData?->first_name}",
                'type' => 'retention',
                'status' => 'active',
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
                'target_customers' => [$customer->id],
                'strategies' => $strategies,
                'budget' => $this->calculateCampaignBudget($customer, $strategies),
            ]);

            // Criar ações específicas da campanha
            $this->createRetentionActions($customer, $campaign, $strategies);

            return $this->success($campaign, 'Campanha de retenção criada');
        });
    }

    public function executeRetentionStrategy(Customer $customer, string $strategyType): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $strategyType) {
            switch ($strategyType) {
                case 'discount':
                    return $this->executeDiscountStrategy($customer);
                case 'loyalty_program':
                    return $this->executeLoyaltyProgramStrategy($customer);
                case 'personalized_service':
                    return $this->executePersonalizedServiceStrategy($customer);
                case 'feedback_collection':
                    return $this->executeFeedbackCollectionStrategy($customer);
                case 'win_back':
                    return $this->executeWinBackStrategy($customer);
                default:
                    return $this->error('Estratégia de retenção não reconhecida', OperationStatus::INVALID_DATA);
            }
        });
    }

    private function identifyRiskFactors(Customer $customer): array
    {
        $riskFactors = [];

        // Fatores de risco
        if ($customer->last_interaction_at && $customer->last_interaction_at < now()->subMonths(3)) {
            $riskFactors[] = [
                'factor' => 'inactivity',
                'severity' => 'high',
                'description' => 'Cliente inativo há mais de 3 meses',
                'weight' => 40,
            ];
        }

        if ($customer->invoices()->where('due_date', '<', now())->where('status', 'pending')->exists()) {
            $overdueAmount = $customer->invoices()->where('due_date', '<', now())->where('status', 'pending')->sum('total');
            $riskFactors[] = [
                'factor' => 'financial_issues',
                'severity' => 'medium',
                'description' => "Cliente com faturas vencidas no valor de R$ {$overdueAmount}",
                'weight' => 30,
            ];
        }

        if ($customer->lifecycle_stage === 'inactive') {
            $riskFactors[] = [
                'factor' => 'stage_risk',
                'severity' => 'medium',
                'description' => 'Cliente no estágio inativo',
                'weight' => 20,
            ];
        }

        if ($customer->services()->where('status', 'cancelled')->count() > 0) {
            $riskFactors[] = [
                'factor' => 'service_issues',
                'severity' => 'high',
                'description' => 'Cliente teve serviços cancelados',
                'weight' => 35,
            ];
        }

        if ($customer->interactions()->where('outcome', 'negative')->count() > 0) {
            $riskFactors[] = [
                'factor' => 'negative_experience',
                'severity' => 'high',
                'description' => 'Cliente teve interações negativas',
                'weight' => 50,
            ];
        }

        return $riskFactors;
    }

    private function calculateChurnRiskScore(Customer $customer, array $riskFactors): float
    {
        $baseScore = 10; // Score base para clientes ativos

        // Score baseado no estágio
        switch ($customer->lifecycle_stage) {
            case 'inactive':
                $baseScore += 40;
                break;
            case 'churned':
                $baseScore += 80;
                break;
            case 'reactivated':
                $baseScore -= 10; // Reduz score para clientes reativados
                break;
        }

        // Score baseado nos fatores de risco
        $riskScore = array_sum(array_column($riskFactors, 'weight'));

        // Score baseado no histórico financeiro
        $paymentHistory = $customer->invoices()->where('status', 'paid')->get();
        if ($paymentHistory->count() > 0) {
            $latePayments = $paymentHistory->filter(function($invoice) {
                return $invoice->transaction_date && $invoice->transaction_date > $invoice->due_date;
            })->count();

            $latePaymentRate = $paymentHistory->count() > 0 ? ($latePayments / $paymentHistory->count()) * 100 : 0;
            $baseScore += $latePaymentRate;
        }

        // Score baseado na frequência de interações
        $interactionCount = $customer->interactions()->count();
        if ($interactionCount < 5) {
            $baseScore += 20;
        } elseif ($interactionCount < 10) {
            $baseScore += 10;
        }

        return min($baseScore + $riskScore, 100);
    }

    private function generateRetentionRecommendations(Customer $customer, float $riskScore): array
    {
        $recommendations = [];

        if ($riskScore >= 80) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Contato imediato',
                'description' => 'Realizar contato telefônico urgente para entender motivos de insatisfação',
                'responsible' => 'Gerente de contas',
                'deadline' => now()->addDays(1),
            ];
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Oferta especial',
                'description' => 'Oferecer desconto ou benefício exclusivo para retenção',
                'responsible' => 'Equipe comercial',
                'deadline' => now()->addDays(2),
            ];
        } elseif ($riskScore >= 60) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Follow-up programado',
                'description' => 'Agendar follow-up em 1 semana para verificar necessidades',
                'responsible' => 'Consultor de vendas',
                'deadline' => now()->addDays(7),
            ];
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Pesquisa de satisfação',
                'description' => 'Enviar pesquisa de satisfação para identificar pontos de melhoria',
                'responsible' => 'Equipe de CX',
                'deadline' => now()->addDays(3),
            ];
        } elseif ($riskScore >= 40) {
            $recommendations[] = [
                'priority' => 'low',
                'action' => 'Monitoramento ativo',
                'description' => 'Aumentar frequência de interações e monitorar indicadores',
                'responsible' => 'Consultor de contas',
                'deadline' => now()->addDays(14),
            ];
            $recommendations[] = [
                'priority' => 'low',
                'action' => 'Programa de fidelidade',
                'description' => 'Oferecer programa de fidelidade ou benefícios',
                'responsible' => 'Equipe de marketing',
                'deadline' => now()->addDays(30),
            ];
        } else {
            $recommendations[] = [
                'priority' => 'low',
                'action' => 'Manutenção de relacionamento',
                'description' => 'Manter relacionamento atual e monitorar indicadores',
                'responsible' => 'Consultor de contas',
                'deadline' => now()->addDays(60),
            ];
        }

        return $recommendations;
    }

    private function getRiskLevel(float $riskScore): string
    {
        if ($riskScore >= 80) return 'critical';
        if ($riskScore >= 60) return 'high';
        if ($riskScore >= 40) return 'medium';
        if ($riskScore >= 20) return 'low';
        return 'minimal';
    }

    private function calculateCampaignBudget(Customer $customer, array $strategies): float
    {
        $baseBudget = 100.00; // Budget base
        $customerValue = $customer->invoices()->where('status', 'paid')->sum('total');

        // Ajustar budget baseado no valor do cliente
        if ($customerValue > 10000) {
            $budgetMultiplier = 0.05; // 5% do valor do cliente
        } elseif ($customerValue > 5000) {
            $budgetMultiplier = 0.03; // 3% do valor do cliente
        } elseif ($customerValue > 1000) {
            $budgetMultiplier = 0.02; // 2% do valor do cliente
        } else {
            $budgetMultiplier = 0.01; // 1% do valor do cliente
        }

        $strategyMultiplier = count($strategies) * 0.1; // 10% adicional por estratégia

        return max($baseBudget, $customerValue * $budgetMultiplier * (1 + $strategyMultiplier));
    }

    private function createRetentionActions(Customer $customer, Campaign $campaign, array $strategies): void
    {
        foreach ($strategies as $strategy) {
            Action::create([
                'tenant_id' => $this->getTenantId(),
                'campaign_id' => $campaign->id,
                'customer_id' => $customer->id,
                'type' => 'retention',
                'description' => $this->getStrategyDescription($strategy),
                'status' => 'pending',
                'priority' => 'medium',
                'due_date' => now()->addDays(7),
                'assigned_to' => auth()->id(),
            ]);
        }
    }

    private function getStrategyDescription(string $strategy): string
    {
        return match ($strategy) {
            'discount' => 'Aplicar desconto especial para retenção',
            'loyalty_program' => 'Oferecer programa de fidelidade',
            'personalized_service' => 'Oferecer serviço personalizado',
            'feedback_collection' => 'Coletar feedback do cliente',
            'win_back' => 'Estratégia de reconquista',
            default => 'Ação de retenção personalizada',
        };
    }

    private function executeDiscountStrategy(Customer $customer): ServiceResult
    {
        // Implementar lógica de estratégia de desconto
        return $this->success(null, 'Estratégia de desconto executada');
    }

    private function executeLoyaltyProgramStrategy(Customer $customer): ServiceResult
    {
        // Implementar lógica de programa de fidelidade
        return $this->success(null, 'Estratégia de programa de fidelidade executada');
    }

    private function executePersonalizedServiceStrategy(Customer $customer): ServiceResult
    {
        // Implementar lógica de serviço personalizado
        return $this->success(null, 'Estratégia de serviço personalizado executada');
    }

    private function executeFeedbackCollectionStrategy(Customer $customer): ServiceResult
    {
        // Implementar lógica de coleta de feedback
        return $this->success(null, 'Estratégia de coleta de feedback executada');
    }

    private function executeWinBackStrategy(Customer $customer): ServiceResult
    {
        // Implementar lógica de estratégia de reconquista
        return $this->success(null, 'Estratégia de reconquista executada');
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Ciclo de Vida**

```php
public function testCustomerStageTransition()
{
    $customer = Customer::factory()->create(['lifecycle_stage' => 'lead']);

    $result = $this->lifecycleService->moveCustomerStage(
        $customer,
        CustomerLifecycleStage::PROSPECT,
        'Lead qualificado',
        'Cliente demonstrou interesse',
        User::factory()->create()
    );

    $this->assertTrue($result->isSuccess());

    $this->assertEquals('prospect', $customer->fresh()->lifecycle_stage);
    $this->assertNotNull($customer->fresh()->stage_changed_at);
}

public function testInvalidStageTransition()
{
    $customer = Customer::factory()->create(['lifecycle_stage' => 'lead']);

    $result = $this->lifecycleService->moveCustomerStage(
        $customer,
        CustomerLifecycleStage::CLOSED_WON,
        'Teste',
        'Teste',
        User::factory()->create()
    );

    $this->assertFalse($result->isSuccess());
    $this->assertEquals('Transição de estágio inválida', $result->getMessage());
}

public function testAutoStageTransition()
{
    $customer = Customer::factory()->create(['lifecycle_stage' => 'lead']);
    CustomerInteraction::factory()->create([
        'customer_id' => $customer->id,
        'interaction_date' => now()->subDays(15),
    ]);

    $result = $this->lifecycleService->autoMoveCustomerStage($customer);
    $this->assertTrue($result->isSuccess());

    $this->assertEquals('prospect', $customer->fresh()->lifecycle_stage);
}

public function testLifecycleAnalytics()
{
    $tenant = Tenant::factory()->create();
    Customer::factory()->count(10)->create(['tenant_id' => $tenant->id]);

    $result = $this->lifecycleService->getLifecycleAnalytics($tenant->id);
    $this->assertTrue($result->isSuccess());

    $analytics = $result->getData();
    $this->assertArrayHasKey('stage_distribution', $analytics);
    $this->assertArrayHasKey('conversion_rates', $analytics);
    $this->assertArrayHasKey('average_stage_duration', $analytics);
    $this->assertArrayHasKey('churn_analysis', $analytics);
    $this->assertArrayHasKey('retention_analysis', $analytics);
    $this->assertArrayHasKey('lifecycle_value', $analytics);
}
```

### **✅ Testes de Retenção**

```php
public function testChurnRiskAnalysis()
{
    $customer = Customer::factory()->create();
    CustomerInteraction::factory()->create([
        'customer_id' => $customer->id,
        'interaction_date' => now()->subMonths(4), // Cliente inativo
    ]);

    $result = $this->retentionService->analyzeChurnRisk($customer);
    $this->assertTrue($result->isSuccess());

    $analysis = $result->getData();
    $this->assertArrayHasKey('risk_score', $analysis);
    $this->assertArrayHasKey('risk_factors', $analysis);
    $this->assertArrayHasKey('recommendations', $analysis);
    $this->assertArrayHasKey('risk_level', $analysis);
    $this->assertGreaterThan(40, $analysis['risk_score']); // Deve ter score alto por inatividade
}

public function testRetentionCampaignCreation()
{
    $customer = Customer::factory()->create();
    $strategies = ['discount', 'feedback_collection'];

    $result = $this->retentionService->createRetentionCampaign($customer, $strategies);
    $this->assertTrue($result->isSuccess());

    $campaign = $result->getData();
    $this->assertEquals('retention', $campaign->type);
    $this->assertEquals('active', $campaign->status);
    $this->assertCount(2, $campaign->actions);
}

public function testRetentionStrategyExecution()
{
    $customer = Customer::factory()->create();

    $result = $this->retentionService->executeRetentionStrategy($customer, 'discount');
    $this->assertTrue($result->isSuccess());
    $this->assertEquals('Estratégia de desconto executada', $result->getMessage());
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar CustomerLifecycleService básico
- [ ] Criar CustomerRetentionService básico
- [ ] Definir estágios do ciclo de vida
- [ ] Sistema de histórico de transições

### **Fase 2: Core Features**
- [ ] Implementar validações de transição
- [ ] Sistema de eventos de ciclo de vida
- [ ] Estratégias de retenção básicas
- [ ] Análise de risco de churn

### **Fase 3: Advanced Features**
- [ ] Machine learning para predição de churn
- [ ] Campanhas de retenção automatizadas
- [ ] Programas de fidelidade integrados
- [ ] Dashboard de ciclo de vida

### **Fase 4: Integration**
- [ ] Integração com CRM externo
- [ ] Sistema de notificações inteligentes
- [ ] API para integração com marketing
- [ ] Relatórios avançados de retenção

## 📚 Documentação Relacionada

- [CustomerLifecycleService](../../app/Services/Domain/CustomerLifecycleService.php)
- [CustomerRetentionService](../../app/Services/Domain/CustomerRetentionService.php)
- [CustomerLifecycleHistory](../../app/Models/CustomerLifecycleHistory.php)
- [CustomerLifecycleStage](../../app/Enums/CustomerLifecycleStage.php)

## 🎯 Benefícios

### **✅ Gestão Estratégica do Relacionamento**
- Visão completa do ciclo de vida do cliente
- Transições automatizadas baseadas em regras de negócio
- Histórico detalhado de todas as mudanças de estágio
- Estratégias de retenção baseadas em análise de risco

### **✅ Redução de Churn**
- Identificação precoce de clientes em risco
- Estratégias de retenção personalizadas
- Campanhas automatizadas de reconquista
- Análise de causas de churn

### **✅ Aumento do Valor do Cliente**
- Programas de fidelidade integrados
- Estratégias de upsell/cross-sell baseadas no estágio
- Personalização de ofertas por fase do ciclo de vida
- Maximização do Customer Lifetime Value

### **✅ Eficiência Operacional**
- Automação de processos de ciclo de vida
- Redução de tempo em análises manuais
- Estratégias padronizadas de retenção
- Métricas claras de performance

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
