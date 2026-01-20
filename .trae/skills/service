---
name: service-lifecycle
description: Garante a integridade do ciclo de vida de serviços e integração com orçamentos no Easy Budget.
---

# Ciclo de Vida de Serviços do Easy Budget

Esta skill define as regras de negócio para o ciclo de vida de serviços (`Service`), sua hierarquia com orçamentos (`Budget`) e integração com itens (`ServiceItem`).

## Hierarquia de Entidades

```
📋 Budget (Orçamento) [Pai]
├── 💼 Service 1 [Filho]
│   └── 📦 ServiceItem 1.1 [Neto]
│   └── 📦 ServiceItem 1.2
├── 💼 Service 2
│   └── 📦 ServiceItem 2.1
└── 📄 Status do Orçamento afeta todos os Serviços
```

## Ciclo de Vida do Orçamento

| Status | Descrição | Transições Permitidas |
|--------|-----------|----------------------|
| **DRAFT** | Criação/Edição. Único status que permite alterações. | PENDING, CANCELLED |
| **PENDING** | Aguardando cliente. Bloqueia qualquer edição. | APPROVED, REJECTED, EXPIRED, CANCELLED |
| **APPROVED** | Aprovado pelo cliente. Habilita agendamento. | IN_PROGRESS, CANCELLED |
| **IN_PROGRESS** | Serviços estão sendo executados. | COMPLETED, CANCELLED |
| **COMPLETED** | Finalizado com sucesso. | (Estado Final) |
| **REJECTED** | Rejeitado pelo cliente. | (Estado Final) |
| **CANCELLED** | Cancelado manualmente pelo prestador. | (Estado Final) |

## Ciclo de Vida do Serviço

| Status | Gatilho de Entrada | Ações do Sistema |
|--------|-------------------|------------------|
| **DRAFT** | Criação do serviço. | Nenhuma ação externa. |
| **PENDING** | Orçamento enviado (PENDING). | Aguarda aprovação do orçamento. |
| **SCHEDULING** | Orçamento aprovado (APPROVED). | Habilita botão de agendamento. |
| **SCHEDULED** | Agendamento definido. | Cria registro na agenda, gera Token e envia E-mail. |
| **PREPARING** | Preparação manual. | Prepara insumos/estoque. |
| **IN_PROGRESS** | Início da execução. | Envia notificação de "Em andamento". |
| **ON_HOLD** | Pausa manual. | Envia notificação de "Pausa". |
| **COMPLETED** | Conclusão manual. | Envia notificação de "Concluído". |

## Padrão de Service de Status

```php
<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Models\Service;
use App\Models\Budget;
use App\Enums\ServiceStatus;
use App\Enums\BudgetStatus;
use App\Support\ServiceResult;
use App\Services\Application\ServiceStatusService;
use Exception;

class BudgetStatusService
{
    public function __construct(
        private ServiceStatusService $serviceStatusService
    ) {}

    /**
     * Altera status do orçamento e sincroniza serviços.
     */
    public function changeBudgetStatus(Budget $budget, string $newStatus): ServiceResult
    {
        try {
            // Validar transição
            $validation = $this->validateStatusTransition($budget->status, $newStatus);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // Atualizar orçamento
            $budget->update(['status' => $newStatus]);

            // Sincronizar serviços baseado no novo status
            $this->syncServicesStatus($budget, $newStatus);

            return ServiceResult::success(
                ['budget_status' => $newStatus],
                'Status do orçamento alterado com sucesso.'
            );
        } catch (Exception $e) {
            return ServiceResult::error($e->getMessage());
        }
    }

    /**
     * Valida se a transição de status é permitida.
     */
    protected function validateStatusTransition(
        string $currentStatus,
        string $newStatus
    ): ServiceResult {
        $allowedTransitions = [
            BudgetStatus::DRAFT->value => [BudgetStatus::PENDING->value, BudgetStatus::CANCELLED->value],
            BudgetStatus::PENDING->value => [
                BudgetStatus::APPROVED->value,
                BudgetStatus::REJECTED->value,
                BudgetStatus::EXPIRED->value,
                BudgetStatus::CANCELLED->value
            ],
            BudgetStatus::APPROVED->value => [BudgetStatus::IN_PROGRESS->value, BudgetStatus::CANCELLED->value],
            BudgetStatus::IN_PROGRESS->value => [BudgetStatus::COMPLETED->value, BudgetStatus::CANCELLED->value],
        ];

        if (!isset($allowedTransitions[$currentStatus])) {
            return ServiceResult::error('Status atual inválido.');
        }

        if (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return ServiceResult::error(
                "Transição de status não permitida: {$currentStatus} → {$newStatus}"
            );
        }

        return ServiceResult::success(null);
    }

    /**
     * Sincroniza status de todos os serviços vinculados.
     */
    protected function syncServicesStatus(Budget $budget, string $newStatus): void
    {
        $budget->loadMissing('services');

        foreach ($budget->services as $service) {
            $serviceStatus = $this->mapBudgetStatusToServiceStatus($newStatus);
            $this->serviceStatusService->changeStatus($service, $serviceStatus);
        }
    }

    /**
     * Mapeia status do orçamento para status do serviço.
     */
    protected function mapBudgetStatusToServiceStatus(string $budgetStatus): string
    {
        return match ($budgetStatus) {
            BudgetStatus::PENDING->value => ServiceStatus::PENDING->value,
            BudgetStatus::APPROVED->value => ServiceStatus::SCHEDULING->value,
            BudgetStatus::IN_PROGRESS->value => ServiceStatus::IN_PROGRESS->value,
            BudgetStatus::CANCELLED->value, BudgetStatus::REJECTED->value => ServiceStatus::DRAFT->value,
            default => $budgetStatus,
        };
    }

    /**
     * Verifica se orçamento pode ser concluído.
     */
    public function canCompleteBudget(Budget $budget): ServiceResult
    {
        $budget->loadMissing('services');

        $pendingServices = $budget->services->filter(function ($service) {
            return !in_array($service->status, [
                ServiceStatus::COMPLETED->value,
                ServiceStatus::CANCELLED->value,
            ]);
        });

        if ($pendingServices->isNotEmpty()) {
            return ServiceResult::error(
                "Não é possível finalizar o orçamento. Existem {$pendingServices->count()} serviço(s) pendente(s)."
            );
        }

        return ServiceResult::success(null, 'Orçamento pode ser concluído.');
    }
}
```

## Regras de Negócio Críticas

### 1. Hierarquia Rígida

```php
// ❌ Incorreto - Atualizar status sem considerar hierarquia
$budget->update(['status' => 'APPROVED']);

// ✅ Correto - Usar service que sincroniza serviços
$statusService->changeBudgetStatus($budget, BudgetStatus::APPROVED);
```

### 2. Sincronia de Status

```php
// Regra: Alterar status do Orçamento força atualização de todos os Serviços
// Exemplo: Se cliente rejeita orçamento, serviços voltam para DRAFT
```

### 3. Imutabilidade

```php
// Regra: Orçamentos enviados (PENDING) são travados para edição
if ($budget->status === BudgetStatus::PENDING->value) {
    return ServiceResult::error('Orçamentos enviados não podem ser editados.');
}
```

### 4. Totalização Automática

```php
// Regra: Valor do Orçamento = Soma dos Serviços
// Regra: Valor do Serviço = Soma dos Itens
```

### 5. Bloqueio de Conclusão

```php
// Regra: Orçamento só pode ser COMPLETED se TODOS os serviços estiverem finalizados
$statusService->canCompleteBudget($budget); // Verifica antes de concluir
```

### 6. Validação de Datas

```php
// Regra: Sistema impede agendamentos com datas retroativas
if ($scheduledDate->isPast()) {
    return ServiceResult::error('Não é possível agendar datas retroativas.');
}
```
