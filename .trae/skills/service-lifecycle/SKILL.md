# 🔄 Skill: Service Lifecycle Management

**Descrição:** Garante o controle correto do ciclo de vida de serviços e integração com orçamentos no Easy Budget.

**Categoria:** Gestão de Serviços
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 📊 Análise Comparativa: Sistema Legado vs. Laravel

### **🔍 Visão do Sistema Legado (Twig + DoctrineDBAL)**

#### **📋 Interface do Usuário (show.twig)**

**Status de Serviços Disponíveis:**
- **DRAFT** (Rascunho)
- **PENDING** (Pendente)
- **SCHEDULING** (Agendando)
- **SCHEDULED** (Agendado)
- **PREPARING** (Preparando)
- **IN_PROGRESS** (Em Progresso)
- **PARTIAL** (Parcial)
- **COMPLETED** (Concluído)
- **ON_HOLD** (Em Espera)
- **CANCELLED** (Cancelado)
- **NOT_PERFORMED** (Não Realizado)
- **EXPIRED** (Expirado)

#### **🔄 Transições de Status Complexas**

```php
// Sistema Legado - Transições detalhadas
case 'SCHEDULING':
    if ($current_status_slug == 'PENDING') {
        // Validação de itens
        if (empty($serviceItems)) {
            return ['status' => 'error', 'message' => 'Não é possível alterar o status do serviço sem items adicionados.'];
        }
        $result = $this->changeStatus($service, $newServiceStatusesToArray);
    }
    break;

case 'IN_PROGRESS':
    if ($current_status_slug == 'PREPARING') {
        // Validação de itens + Notificação por e-mail
        if (empty($serviceItems)) {
            return ['status' => 'error', 'message' => 'Não é possível alterar o status do serviço serviço sem items adicionados.'];
        }
        $result = $this->changeStatus($service, $newServiceStatusesToArray);
        if ($result['status'] === 'success') {
            // Envio de notificação por e-mail
            $emailSent = $this->notificationService->sendServiceStatusUpdate(...);
        }
    }
    break;

case 'CANCELLED':
    // Lógica especial para IN_PROGRESS -> PARTIAL
    if ($current_status_slug === 'IN_PROGRESS') {
        $newServiceStatuses = $this->serviceStatuses->getStatusBySlug('PARTIAL');
        $result = $this->changeStatus($service, $newServiceStatusesToArray);
    } else {
        // Lógica padrão
        $result = $this->changeStatus($service, $newServiceStatusesToArray);
    }
    break;
```

#### **📅 Sistema de Agendamento Completo**

```php
// Sistema Legado - Agendamento avançado
case 'SCHEDULED':
    if ($current_status_slug == 'SCHEDULING' or $current_status_slug == 'ON_HOLD') {
        // Criação de token de confirmação
        $result = $this->sharedService->generateNewUserConfirmationToken($this->authenticated->user_id, $this->authenticated->tenant_id);

        // Criação de agendamento
        $scheduleEntity = ScheduleEntity::create([...]);
        $result = $this->schedule->create($scheduleEntity);

        // Notificação por e-mail
        $emailSent = $this->notificationService->sendServiceStatusUpdate(...);
    }
    break;
```

### **🏗️ Arquitetura do Sistema Legado**

#### **📊 Controller Complexo (ServiceController.php)**

```php
// Sistema Legado - 615 linhas de lógica complexa
class ServiceController extends AbstractController {
    public function change_status(): Response {
        // 1. Validação de formulário
        $validated = ServiceChangeStatusFormRequest::validate($this->request);

        // 2. Lógica de mudança de status
        $response = $this->serviceService->handleStatusChange($data, $this->authenticated);

        // 3. Auditoria de atividades
        $this->activityLogger(...);

        // 4. Redirecionamento
        return Redirect::redirect('/provider/services/show/'.$data['service_code'])
            ->withMessage('success', 'Status do serviço atualizado com sucesso!');
    }
}
```

#### **🔧 Service Complexo (ServiceService.php)**

```php
// Sistema Legado - 1115 linhas de lógica de negócio
class ServiceService {
    public function handleStatusChange(array $data, object $authenticated): array {
        // 1. Validação de status atual
        // 2. Validação de transição permitida
        // 3. Validação de itens
        // 4. Criação de agendamentos
        // 5. Envio de notificações
        // 6. Atualização de orçamentos
        // 7. Auditoria de mudanças
    }

    public function changeStatus(array $service, array $newServiceStatuses, array $data = []): array {
        // 1. Atualização do serviço
        // 2. Criação de agendamentos (se necessário)
        // 3. Validação de tokens
        // 4. Notificações
    }
}
```

### **🎯 Sistema Laravel Atual - Simplificação Estratégica**

#### **📊 Status do Sistema Antigo (COMPLETOS)**

```php
// Sistema Antigo - 12 status completos (MANTER ESTA LÓGICA)
enum ServiceStatus: string {
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case SCHEDULING = 'scheduling';
    case SCHEDULED = 'scheduled';
    case PREPARING = 'preparing';
    case IN_PROGRESS = 'in_progress';
    case PARTIAL = 'partial';
    case COMPLETED = 'completed';
    case ON_HOLD = 'on_hold';
    case CANCELLED = 'cancelled';
    case NOT_PERFORMED = 'not_performed';
    case EXPIRED = 'expired';
}
```

#### **🔄 Transições COMPLEXAS do Sistema Antigo (MANTER)**

```php
// Sistema Antigo - Transições detalhadas (MANTER ESTA LÓGICA)
class ServiceLifecycleService {
    public function changeStatus(Service $service, ServiceStatus $newStatus, array $data = []): ServiceResult {
        $currentStatus = $service->status;

        // 1. Validação de transições específicas
        switch ($newStatus) {
            case ServiceStatus::SCHEDULING:
                if ($currentStatus !== ServiceStatus::PENDING) {
                    return $this->error('Só é possível agendar serviços pendentes', OperationStatus::INVALID_DATA);
                }
                // Validação de itens
                if (empty($service->items)) {
                    return $this->error('Não é possível agendar serviço sem itens adicionados', OperationStatus::INVALID_DATA);
                }
                break;

            case ServiceStatus::SCHEDULED:
                if (!in_array($currentStatus, [ServiceStatus::SCHEDULING, ServiceStatus::ON_HOLD])) {
                    return $this->error('Transição inválida para agendado', OperationStatus::INVALID_DATA);
                }
                // Criação de token de confirmação
                $token = $this->generateUserConfirmationToken($service->user_id, $service->tenant_id);
                // Criação de agendamento
                $schedule = $this->createSchedule($service, $data['schedule_data']);
                break;

            case ServiceStatus::IN_PROGRESS:
                if ($currentStatus !== ServiceStatus::PREPARING) {
                    return $this->error('Só é possível iniciar serviços em preparação', OperationStatus::INVALID_DATA);
                }
                // Validação de itens + Notificação por e-mail
                if (empty($service->items)) {
                    return $this->error('Não é possível iniciar serviço sem itens adicionados', OperationStatus::INVALID_DATA);
                }
                // Envio de notificação por e-mail
                $this->sendServiceStatusUpdate($service, $newStatus);
                break;

            case ServiceStatus::CANCELLED:
                // Lógica especial para IN_PROGRESS -> PARTIAL
                if ($currentStatus === ServiceStatus::IN_PROGRESS) {
                    $newStatus = ServiceStatus::PARTIAL; // Mudança especial
                }
                break;

            case ServiceStatus::COMPLETED:
                if ($currentStatus !== ServiceStatus::IN_PROGRESS) {
                    return $this->error('Só é possível concluir serviços em progresso', OperationStatus::INVALID_DATA);
                }
                break;
        }

        // 2. Executar transição
        return $this->repository->update($service, ['status' => $newStatus->value]);
    }
}
```

#### **📅 Agendamento Simplificado**

```php
// Sistema Laravel - Agendamento básico
class ServiceLifecycleService {
    public function scheduleService(Service $service, array $scheduleData): ServiceResult {
        // 1. Validar dados de agendamento
        // 2. Criar agendamento
        // 3. Atualizar status do serviço
        // 4. Disparar eventos
    }
}
```

### **📊 Comparação de Complexidade**

| **Aspecto** | **Sistema Legado** | **Sistema Laravel (ATUALIZADO)** | **Benefício** |
|-------------|-------------------|----------------------------------|---------------|
| **Status disponíveis** | 12 status complexos | 12 status complexos (MANTIDOS) | ✅ Fidelidade ao legado |
| **Transições de status** | 50+ regras complexas | 50+ regras complexas (MANTIDAS) | ✅ Funcionalidade completa |
| **Lógica de agendamento** | Sistema completo com tokens | Sistema completo com tokens (MANTIDO) | ✅ Funcionalidade preservada |
| **Notificações** | E-mail automático complexo | E-mail automático complexo (MANTIDO) | ✅ Experiência do usuário |
| **Validações** | Validações inline complexas | Validações inline complexas (MANTIDAS) | ✅ Controle rigoroso |
| **Auditoria** | Auditoria manual detalhada | Auditoria manual detalhada (MANTIDA) | ✅ Conformidade preservada |

### **🚀 Decisões de Manutenção da Complexidade**

#### **✅ Decisões Corretas (MANTIDAS)**

1. **Todos os 12 Status:** Manutenção de todos os status originais (SCHEDULING, PREPARING, ON_HOLD, NOT_PERFORMED)
2. **Transições Complexas:** Manutenção de todas as transições originais para preservar a lógica de negócio
3. **Notificações Inline:** Manutenção de notificações por e-mail inline para experiência do usuário
4. **Validações Complexas:** Manutenção de validações inline para controle rigoroso

#### **✅ Benefícios da Manutenção**

1. **Fidelidade ao Legado:** Sistema mantém todas as funcionalidades originais
2. **Experiência do Usuário:** Fluxo de trabalho completo preservado
3. **Controle de Qualidade:** Validções rigorosas mantidas
4. **Auditoria Completa:** Histórico detalhado preservado

### **🎯 Recomendações para Implementação**

#### **✅ Manter a Complexidade do Sistema Legado**

1. **Todos os 12 Status:** Implementar todos os status originais sem simplificação
2. **ServiceResult Pattern:** Usar ServiceResult para consistência, mas manter lógica complexa
3. **Notificações Inline:** Manter notificações por e-mail inline para experiência do usuário
4. **Validações Complexas:** Manter validações inline para controle rigoroso

#### **🔄 Implementação do Sistema Legado**

1. **Sistema de Agendamento Completo:** Implementar com tokens de confirmação
2. **Notificações por E-mail:** Implementar notificações inline complexas
3. **Workflows Complexos:** Implementar todas as transições originais
4. **Auditoria Detalhada:** Implementar auditoria manual detalhada

### **📊 Conclusão da Análise**

**O sistema Laravel deve preservar a complexidade do sistema legado:**

- ✅ **Fidelidade ao Legado:** Manutenção de todos os 12 status originais
- ✅ **Funcionalidade Completa:** Todas as transições e validações preservadas
- ✅ **Experiência do Usuário:** Fluxo de trabalho completo mantido
- ✅ **Controle de Qualidade:** Validções rigorosas preservadas

**A manutenção da complexidade preserva a funcionalidade essencial e garante que o novo sistema ofereça todas as capacidades do legado.**

## 🎯 Objetivo

Padronizar o ciclo de vida completo dos serviços no Easy Budget, desde a criação até a conclusão, garantindo integração correta com orçamentos, controle de status e rastreamento de mudanças.

## 📋 Requisitos Técnicos

### **✅ Status de Serviços**

Implementar enumeração completa de status para serviços:

```php
enum ServiceStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case SCHEDULING = 'scheduling';
    case SCHEDULED = 'scheduled';
    case PREPARING = 'preparing';
    case IN_PROGRESS = 'in_progress';
    case PARTIAL = 'partial';
    case COMPLETED = 'completed';
    case ON_HOLD = 'on_hold';
    case CANCELLED = 'cancelled';
    case NOT_PERFORMED = 'not_performed';
    case EXPIRED = 'expired';

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::SCHEDULING, self::SCHEDULED, self::PREPARING, self::IN_PROGRESS]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::NOT_PERFORMED, self::EXPIRED]);
    }

    public function canHaveItems(): bool
    {
        return in_array($this, [self::PENDING, self::SCHEDULING, self::SCHEDULED, self::PREPARING, self::IN_PROGRESS]);
    }

    public function requiresConfirmation(): bool
    {
        return in_array($this, [self::SCHEDULED, self::IN_PROGRESS]);
    }
}
```

### **✅ Transições de Status Controladas**

```php
class ServiceLifecycleService extends AbstractBaseService
{
    public function changeStatus(Service $service, ServiceStatus $newStatus): ServiceResult
    {
        // 1. Validar transição permitida
        if (!$this->isValidTransition($service->status, $newStatus)) {
            return $this->error(
                'Transição de status não permitida',
                OperationStatus::INVALID_DATA
            );
        }

        // 2. Validar regras de negócio (baseadas no sistema legado)
        if (!$this->validateBusinessRules($service, $newStatus)) {
            return $this->error(
                'Regras de negócio não atendidas',
                OperationStatus::INVALID_DATA
            );
        }

        // 3. Executar transição
        return $this->repository->update($service, ['status' => $newStatus->value]);
    }

    private function isValidTransition(ServiceStatus $current, ServiceStatus $new): bool
    {
        // Transições do sistema legado (MANTER TODAS)
        $validTransitions = [
            ServiceStatus::DRAFT => [ServiceStatus::PENDING, ServiceStatus::CANCELLED],
            ServiceStatus::PENDING => [ServiceStatus::SCHEDULING, ServiceStatus::CANCELLED],
            ServiceStatus::SCHEDULING => [ServiceStatus::SCHEDULED, ServiceStatus::ON_HOLD, ServiceStatus::CANCELLED],
            ServiceStatus::SCHEDULED => [ServiceStatus::PREPARING, ServiceStatus::ON_HOLD, ServiceStatus::CANCELLED],
            ServiceStatus::PREPARING => [ServiceStatus::IN_PROGRESS, ServiceStatus::ON_HOLD, ServiceStatus::CANCELLED],
            ServiceStatus::IN_PROGRESS => [ServiceStatus::COMPLETED, ServiceStatus::PARTIAL, ServiceStatus::ON_HOLD, ServiceStatus::CANCELLED],
            ServiceStatus::PARTIAL => [ServiceStatus::IN_PROGRESS, ServiceStatus::COMPLETED, ServiceStatus::CANCELLED],
            ServiceStatus::COMPLETED => [ServiceStatus::CANCELLED],
            ServiceStatus::ON_HOLD => [ServiceStatus::SCHEDULING, ServiceStatus::PREPARING, ServiceStatus::IN_PROGRESS, ServiceStatus::CANCELLED],
            ServiceStatus::CANCELLED => [],
            ServiceStatus::NOT_PERFORMED => [],
            ServiceStatus::EXPIRED => []
        ];

        return in_array($new, $validTransitions[$current] ?? []);
    }

    private function validateBusinessRules(Service $service, ServiceStatus $newStatus): bool
    {
        // Regras de negócio específicas (baseadas no sistema legado)
        switch ($newStatus) {
            case ServiceStatus::SCHEDULING:
                return $this->validateSchedulingRules($service);
            case ServiceStatus::SCHEDULED:
                return $this->validateScheduledRules($service);
            case ServiceStatus::PREPARING:
                return $this->validatePreparingRules($service);
            case ServiceStatus::IN_PROGRESS:
                return $this->validateInProgressRules($service);
            case ServiceStatus::PARTIAL:
                return $this->validatePartialRules($service);
            case ServiceStatus::COMPLETED:
                return $this->validateCompletedRules($service);
            case ServiceStatus::ON_HOLD:
                return $this->validateOnHoldRules($service);
            case ServiceStatus::CANCELLED:
                return $this->validateCancelledRules($service);
            case ServiceStatus::NOT_PERFORMED:
                return $this->validateNotPerformedRules($service);
            default:
                return true;
        }
    }

    private function validateSchedulingRules(Service $service): bool
    {
        // Validar se o serviço tem itens suficientes para agendamento
        return $service->items()->count() > 0;
    }

    private function validateScheduledRules(Service $service): bool
    {
        // Validar se há data de agendamento definida
        return $service->scheduled_date !== null;
    }

    private function validatePreparingRules(Service $service): bool
    {
        // Validar se o serviço está agendado e pronto para preparação
        return $service->status === ServiceStatus::SCHEDULED->value;
    }

    private function validateInProgressRules(Service $service): bool
    {
        // Validar se o serviço está preparado para início
        return in_array($service->status, [ServiceStatus::PREPARING->value, ServiceStatus::SCHEDULED->value]);
    }

    private function validateStartPrerequisites(Service $service): bool
    {
        // Validar se o serviço está pronto para início
        return in_array($service->status, [
            ServiceStatus::PENDING->value,
            ServiceStatus::PREPARING->value,
            ServiceStatus::SCHEDULED->value
        ]);
    }

    private function validatePartialRules(Service $service): bool
    {
        // Validar se o serviço está em progresso
        return $service->status === ServiceStatus::IN_PROGRESS->value;
    }

    private function validateCompletedRules(Service $service): bool
    {
        // Validar se todos os itens foram concluídos
        return $service->items()->where('completed', false)->count() === 0;
    }

    private function validateCancelledRules(Service $service): bool
    {
        // Validar se não há pagamentos pendentes
        return $service->invoices()->where('status', 'pending')->count() === 0;
    }

    private function validateOnHoldRules(Service $service): bool
    {
        // Validar se o serviço pode ser pausado
        return in_array($service->status, [
            ServiceStatus::SCHEDULING->value,
            ServiceStatus::SCHEDULED->value,
            ServiceStatus::PREPARING->value,
            ServiceStatus::IN_PROGRESS->value
        ]);
    }

    private function validateNotPerformedRules(Service $service): bool
    {
        // Validar se o serviço estava agendado mas não foi realizado
        return $service->status === ServiceStatus::SCHEDULED->value;
    }

    private function validateCancelledRules(Service $service): bool
    {
        // Validar se não há pagamentos pendentes
        return $service->invoices()->where('status', 'pending')->count() === 0;
    }

    private function validateNotPerformedRules(Service $service): bool
    {
        // Validar se o serviço estava agendado mas não foi realizado
        return $service->status === ServiceStatus::SCHEDULED->value;
    }
}
```

## 🏗️ Estrutura do Ciclo de Vida

### **📊 Fluxo Completo de Serviço**

```
┌─────────────┐    ┌─────────────┐    ┌─────────────────┐
│   DRAFT     │───▶│   PENDING   │───▶│   IN_PROGRESS   │
└─────────────┘    └─────────────┘    └─────────────────┘
     │                   │                   │
     │                   │                   │
     ▼                   ▼                   ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────────┐
│  CANCELLED  │    │  CANCELLED  │    │   COMPLETED     │
└─────────────┘    └─────────────┘    └─────────────────┘
```

### **📝 Etapas do Ciclo de Vida**

#### **1. Criação (DRAFT)**
```php
public function createService(array $data): ServiceResult
{
    // 1. Validar dados básicos
    $validation = $this->validate($data);
    if (!$validation->isSuccess()) {
        return $validation;
    }

    // 2. Criar serviço em estado DRAFT
    $serviceData = array_merge($data, [
        'status' => ServiceStatus::DRAFT->value,
        'code' => $this->generateServiceCode(),
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return $this->repository->create($serviceData);
}
```

#### **2. Ativação (PENDING)**
```php
public function activateService(Service $service): ServiceResult
{
    return $this->safeExecute(function() use ($service) {
        // 1. Validar pré-requisitos
        if (!$this->validateActivationPrerequisites($service)) {
            return $this->error('Pré-requisitos não atendidos', OperationStatus::INVALID_DATA);
        }

        // 2. Atualizar status
        $result = $this->changeStatus($service, ServiceStatus::PENDING);

        if ($result->isSuccess()) {
            // 3. Disparar eventos
            event(new ServiceActivated($service));
        }

        return $result;
    });
}
```

#### **3. Execução (IN_PROGRESS)**
```php
public function startService(Service $service): ServiceResult
{
    return $this->safeExecute(function() use ($service) {
        // 1. Validar início
        if (!$this->validateStartConditions($service)) {
            return $this->error('Condições de início não atendidas', OperationStatus::INVALID_DATA);
        }

        // 2. Atualizar status e data de início
        $result = $this->repository->update($service, [
            'status' => ServiceStatus::IN_PROGRESS->value,
            'started_at' => now()
        ]);

        if ($result->isSuccess()) {
            // 3. Disparar eventos
            event(new ServiceStarted($service));
        }

        return $result;
    });
}
```

#### **4. Conclusão (COMPLETED)**
```php
public function completeService(Service $service, array $completionData): ServiceResult
{
    return $this->safeExecute(function() use ($service, $completionData) {
        // 1. Validar conclusão
        if (!$this->validateCompletion($service, $completionData)) {
            return $this->error('Validação de conclusão falhou', OperationStatus::INVALID_DATA);
        }

        // 2. Atualizar dados de conclusão
        $updateData = array_merge($completionData, [
            'status' => ServiceStatus::COMPLETED->value,
            'completed_at' => now(),
            'updated_at' => now()
        ]);

        $result = $this->repository->update($service, $updateData);

        if ($result->isSuccess()) {
            // 3. Disparar eventos
            event(new ServiceCompleted($service, $completionData));
        }

        return $result;
    });
}
```

## 🔗 Integração com Orçamentos

### **✅ Criação a partir de Orçamento**

```php
public function createFromBudget(Budget $budget, array $serviceData): ServiceResult
{
    return $this->safeExecute(function() use ($budget, $serviceData) {
        // 1. Validar orçamento
        if ($budget->status !== BudgetStatus::APPROVED) {
            return $this->error('Orçamento não aprovado', OperationStatus::INVALID_DATA);
        }

        // 2. Criar serviço vinculado ao orçamento
        $serviceData = array_merge($serviceData, [
            'budget_id' => $budget->id,
            'customer_id' => $budget->customer_id,
            'total_value' => $budget->total_value,
            'status' => ServiceStatus::PENDING->value,
            'code' => $this->generateServiceCode(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $result = $this->repository->create($serviceData);

        if ($result->isSuccess()) {
            // 3. Atualizar status do orçamento
            $this->budgetService->updateStatus($budget, BudgetStatus::IN_PROGRESS);
        }

        return $result;
    });
}
```

### **✅ Sincronização de Status**

```php
public function syncBudgetStatus(Service $service): ServiceResult
{
    // 1. Obter orçamento associado
    if (!$service->budget_id) {
        return $this->success(null, 'Serviço não vinculado a orçamento');
    }

    $budget = $this->budgetRepository->findById($service->budget_id);
    if (!$budget) {
        return $this->error('Orçamento não encontrado', OperationStatus::NOT_FOUND);
    }

    // 2. Determinar status do orçamento baseado nos serviços
    $budgetStatus = $this->calculateBudgetStatus($budget);

    // 3. Atualizar status do orçamento
    return $this->budgetService->updateStatus($budget, $budgetStatus);
}

private function calculateBudgetStatus(Budget $budget): BudgetStatus
{
    $services = $this->repository->findByBudgetId($budget->id);

    if ($services->isEmpty()) {
        return BudgetStatus::PENDING;
    }

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
```

## 📊 Controle de Tempo e Prazos

### **✅ Gestão de Prazos**

```php
class ServiceDeadlineService extends AbstractBaseService
{
    public function checkDeadlines(): ServiceResult
    {
        // 1. Obter serviços próximos do vencimento
        $upcomingDeadlines = $this->repository->findUpcomingDeadlines(
            now()->addDays(3),
            [ServiceStatus::PENDING->value, ServiceStatus::IN_PROGRESS->value]
        );

        // 2. Enviar notificações
        foreach ($upcomingDeadlines as $service) {
            $this->sendDeadlineNotification($service);
        }

        // 3. Verificar serviços vencidos
        $expiredServices = $this->repository->findExpiredServices(now());

        foreach ($expiredServices as $service) {
            $this->handleExpiredService($service);
        }

        return $this->success(null, 'Verificação de prazos concluída');
    }

    private function handleExpiredService(Service $service): void
    {
        // 1. Atualizar status para EXPIRED
        $this->changeStatus($service, ServiceStatus::EXPIRED);

        // 2. Disparar notificação
        $this->sendExpirationNotification($service);

        // 3. Atualizar orçamento associado
        $this->syncBudgetStatus($service);
    }
}
```

### **✅ Histórico de Alterações**

```php
class ServiceHistoryService extends AbstractBaseService
{
    public function logStatusChange(Service $service, ServiceStatus $oldStatus, ServiceStatus $newStatus, ?User $user = null): void
    {
        ServiceHistory::create([
            'service_id' => $service->id,
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
            'changed_by' => $user?->id,
            'changed_at' => now(),
            'reason' => $this->getChangeReason($oldStatus, $newStatus)
        ]);
    }

    public function getServiceHistory(Service $service): Collection
    {
        return ServiceHistory::where('service_id', $service->id)
            ->orderBy('changed_at', 'desc')
            ->get();
    }

    private function getChangeReason(ServiceStatus $old, ServiceStatus $new): string
    {
        return match([$old, $new]) {
            [ServiceStatus::DRAFT, ServiceStatus::PENDING] => 'Serviço ativado',
            [ServiceStatus::PENDING, ServiceStatus::IN_PROGRESS] => 'Serviço iniciado',
            [ServiceStatus::IN_PROGRESS, ServiceStatus::COMPLETED] => 'Serviço concluído',
            [ServiceStatus::PENDING, ServiceStatus::CANCELLED] => 'Serviço cancelado',
            [ServiceStatus::IN_PROGRESS, ServiceStatus::CANCELLED] => 'Serviço interrompido',
            default => 'Alteração de status'
        };
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Transição de Status**

```php
public function testValidStatusTransitions()
{
    $service = Service::factory()->create(['status' => ServiceStatus::DRAFT->value]);

    // Testar transição válida: DRAFT -> PENDING
    $result = $this->serviceLifecycleService->changeStatus($service, ServiceStatus::PENDING);
    $this->assertTrue($result->isSuccess());

    // Testar transição inválida: COMPLETED -> PENDING
    $service->update(['status' => ServiceStatus::COMPLETED->value]);
    $result = $this->serviceLifecycleService->changeStatus($service, ServiceStatus::PENDING);
    $this->assertFalse($result->isSuccess());
}

public function testServiceFromBudgetCreation()
{
    $budget = Budget::factory()->approved()->create();

    $serviceData = [
        'description' => 'Test service',
        'due_date' => now()->addDays(7)
    ];

    $result = $this->serviceLifecycleService->createFromBudget($budget, $serviceData);
    $this->assertTrue($result->isSuccess());

    $service = $result->getData();
    $this->assertEquals($budget->id, $service->budget_id);
    $this->assertEquals(ServiceStatus::PENDING->value, $service->status);
}
```

### **✅ Testes de Integração com Orçamentos**

```php
public function testBudgetStatusSync()
{
    $budget = Budget::factory()->create(['status' => BudgetStatus::APPROVED->value]);
    $service = Service::factory()->create([
        'budget_id' => $budget->id,
        'status' => ServiceStatus::IN_PROGRESS->value
    ]);

    $result = $this->serviceLifecycleService->syncBudgetStatus($service);
    $this->assertTrue($result->isSuccess());

    $budget->refresh();
    $this->assertEquals(BudgetStatus::IN_PROGRESS->value, $budget->status);
}
```

## 📈 Métricas e Monitoramento

### **✅ Métricas de Performance**

```php
class ServiceMetricsService extends AbstractBaseService
{
    public function getServiceMetrics(array $filters = []): array
    {
        $services = $this->repository->findWithFilters($filters);

        return [
            'total_services' => $services->count(),
            'active_services' => $services->where('status', 'in_progress')->count(),
            'completed_services' => $services->where('status', 'completed')->count(),
            'average_completion_time' => $this->calculateAverageCompletionTime($services),
            'on_time_completion_rate' => $this->calculateOnTimeCompletionRate($services)
        ];
    }

    private function calculateAverageCompletionTime(Collection $services): float
    {
        $completedServices = $services->where('status', 'completed');

        if ($completedServices->isEmpty()) {
            return 0.0;
        }

        $totalTime = $completedServices->sum(function($service) {
            return $service->completed_at->diffInDays($service->started_at);
        });

        return $totalTime / $completedServices->count();
    }
}
```

### **✅ Alertas e Notificações**

```php
class ServiceAlertService extends AbstractBaseService
{
    public function checkServiceAlerts(): void
    {
        // 1. Serviços próximos do vencimento
        $this->checkDeadlineAlerts();

        // 2. Serviços com tempo de execução acima do esperado
        $this->checkExecutionTimeAlerts();

        // 3. Serviços bloqueados
        $this->checkBlockedServicesAlerts();
    }

    private function checkDeadlineAlerts(): void
    {
        $services = $this->repository->findUpcomingDeadlines(now()->addDays(1));

        foreach ($services as $service) {
            $this->sendNotification(
                $service->assigned_to,
                'Serviço próximo do vencimento',
                "O serviço {$service->code} vence em breve"
            );
        }
    }
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar ServiceStatus enum
- [ ] Criar ServiceLifecycleService
- [ ] Definir validações de transição

### **Fase 2: Core Features**
- [ ] Implementar criação a partir de orçamentos
- [ ] Criar histórico de alterações
- [ ] Implementar controle de prazos

### **Fase 3: Integration**
- [ ] Integrar com orçamentos
- [ ] Criar métricas de performance
- [ ] Implementar alertas e notificações

### **Fase 4: Advanced Features**
- [ ] Dashboard de acompanhamento
- [ ] Relatórios de performance
- [ ] Integração com calendário

## 📚 Documentação Relacionada

- [Service Model](../../app/Models/Service.php)
- [ServiceStatus Enum](../../app/Enums/ServiceStatus.php)
- [ServiceLifecycleService](../../app/Services/Domain/ServiceLifecycleService.php)
- [Service History](../../app/Models/ServiceHistory.php)

## 🎯 Benefícios

### **✅ Controle Total**
- Visibilidade completa do ciclo de vida dos serviços
- Controle de qualidade através de validações
- Histórico detalhado de todas as alterações

### **✅ Integração Perfeita**
- Sincronização automática com orçamentos
- Fluxo de trabalho integrado
- Dados consistentes entre módulos

### **✅ Gestão de Prazos**
- Controle de deadlines e entregas
- Alertas proativos para vencimentos
- Métricas de performance

### **✅ Tomada de Decisão**
- Dashboards com métricas em tempo real
- Histórico de alterações para auditoria
- Relatórios de eficiência e produtividade

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
