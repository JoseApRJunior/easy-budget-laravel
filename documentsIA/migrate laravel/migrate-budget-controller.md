# Plano de Implementação - BudgetController (Laravel 12)

## 📋 Visão Geral

Este documento detalha o plano de migração do `BudgetController` do sistema legado (Twig + DoctrineDBAL) para Laravel 12, considerando as mudanças significativas na arquitetura, especialmente a substituição da tabela `budget_statuses` por um enum `BudgetStatus`.

**Data:** 31/10/2025
**Sistema Legado:** `old-system/app/controllers/BudgetController.php`
**Sistema Novo:** Laravel 12 com arquitetura Controller → Services → Repositories → Models

---

## 🔄 Mudanças Arquiteturais Principais

### 1. **Campo de Status**

-  **Legado:** `budget_statuses_id` (integer) → tabela `budget_statuses`
-  **Novo:** `status` (enum BudgetStatus) → valores string (DRAFT, PENDING, etc.)

### 2. **Arquitetura**

-  **Legado:** Controller direto com models DoctrineDBAL
-  **Novo:** Controller → Services → Repositories → Models (Eloquent)

### 3. **Lógica de Status**

-  **Legado:** Consultas à tabela `budget_statuses` para validações
-  **Novo:** Métodos do enum `BudgetStatus` para controle de fluxo

### 4. **Templates**

-  **Legado:** Twig templates
-  **Novo:** Blade templates

---

## 📊 Análise de Métodos (12 métodos)

### ✅ 1. `index()` - Lista de Orçamentos

**Status:** ✅ Pronto para migração

**Adaptações necessárias:**

-  Usar `BudgetRepository` ao invés de `Budget::getAllBudgets()`
-  Implementar filtros e paginação via `BudgetService`
-  View: `resources/views/pages/budgets/index.blade.php`

### ✅ 2. `create()` - Formulário de Criação

**Status:** ✅ Pronto para migração

**Adaptações necessárias:**

-  Usar `CustomerRepository` para listar clientes
-  View: `resources/views/pages/budgets/create.blade.php`

### ✅ 3. `store()` - Criar Orçamento

**Status:** ⚠️ Requer adaptações

**Mudanças necessárias:**

```php
// LEGADO
$budget_statuses_id = 1; // DRAFT

// NOVO
$status = BudgetStatus::DRAFT;
```

**Adaptações:**

-  Usar `BudgetCodeGeneratorService` para códigos únicos
-  Status inicial: `BudgetStatus::DRAFT`
-  Implementar lock para evitar duplicatas de código

### ✅ 4. `update($code)` - Formulário de Edição

**Status:** ✅ Pronto para migração

**Adaptações necessárias:**

-  Usar `BudgetRepository::findByCodeAndTenantId()`
-  Carregar serviços via `ServiceRepository`
-  View: `resources/views/pages/budgets/edit.blade.php`

### ✅ 5. `update_store()` - Atualizar Orçamento

**Status:** ⚠️ Requer adaptações

**Mudanças necessárias:**

-  Validação de data usando Carbon
-  Comparação de dados usando arrays ao invés de objetos Doctrine
-  Activity logging via `AuditLog` trait

### ✅ 6. `show($code)` - Detalhes do Orçamento

**Status:** ✅ Pronto para migração

**Adaptações necessárias:**

-  Eager loading para relacionamentos
-  Cálculo de `all_services_completed` via `ServiceRepository`
-  View: `resources/views/pages/budgets/show.blade.php`

### ✅ 7. `change_status()` - Alterar Status (Provider)

**Status:** ⚠️ Requer adaptações

**Mudanças necessárias:**

```php
// LEGADO
BudgetService->handleStatusChange()

// NOVO
BudgetStatusService->changeStatusWithCascade()
```

**Adaptações:**

-  Usar `BudgetStatus::canTransitionTo()` para validações
-  Implementar mudança em cascata (orçamento + serviços)

### ✅ 8. `choose_budget_status($code, $token)` - Escolha de Status (Cliente)

**Status:** ⚠️ Requer adaptações

**Mudanças necessárias:**

-  Usar `UserConfirmationToken` model ao invés de `SharedService`
-  Implementar regeneração de token quando expirado
-  View: `resources/views/pages/budgets/choose-status.blade.php`

### ✅ 9. `choose_budget_status_store()` - Salvar Escolha (Cliente)

**Status:** ⚠️ Requer adaptações

**Mudanças necessárias:**

-  Validação de token via `UserConfirmationTokenRepository`
-  Processamento de aprovação/rejeição via `BudgetStatusService`

### ✅ 10. `print($code, $token)` - Gerar PDF

**Status:** ⚠️ Requer adaptações

**Mudanças necessárias:**

-  Usar `BudgetPdfService` ao invés de `BudgetService->printPDF()`
-  Implementar hash de verificação
-  Response com `Content-Type: application/pdf`

### ✅ 11. `delete_store($code)` - Deletar Orçamento

**Status:** ⚠️ Requer adaptações

**Mudanças necessárias:**

-  Verificação de relacionamentos via `BudgetRepository::hasRelationships()`
-  Activity logging via `AuditLog` trait

### ✅ 12. `activityLogger()` - Helper de Log

**Status:** ✅ Substituído

**Mudanças necessárias:**

-  Remover método helper
-  Usar `AuditLog` trait no model `Budget`

---

## 🏗️ Estrutura de Implementação

### Controllers

```php
// Provider (CRUD completo)
app/Http/Controllers/Provider/BudgetController.php

// Cliente (apenas aprovação/visualização)
app/Http/Controllers/Public/PublicBudgetController.php
```

### Services

```php
app/Services/Domain/BudgetService.php              // Lógica principal
app/Services/Domain/BudgetCodeGeneratorService.php // Geração de códigos únicos
app/Services/Domain/BudgetStatusService.php        // Gerenciamento de status
app/Services/Domain/BudgetPdfService.php           // Geração de PDFs
app/Services/Domain/BudgetTokenService.php         // Gestão de tokens
```

### Repositories

```php
app/Repositories/BudgetRepository.php
app/Repositories/ServiceRepository.php
app/Repositories/CustomerRepository.php
app/Repositories/UserConfirmationTokenRepository.php
```

### Form Requests

```php
app/Http/Requests/BudgetStoreRequest.php
app/Http/Requests/BudgetUpdateRequest.php
app/Http/Requests/BudgetChangeStatusRequest.php
app/Http/Requests/BudgetApprovalRequest.php
```

### Events & Listeners

```php
app/Events/BudgetCreated.php
app/Events/BudgetStatusChanged.php
app/Events/BudgetApproved.php
app/Events/BudgetRejected.php

app/Listeners/SendBudgetCreatedNotification.php
app/Listeners/SendBudgetStatusNotification.php
app/Listeners/SendBudgetApprovedNotification.php
app/Listeners/SendBudgetRejectedNotification.php
```

### Policies

```php
app/Policies/BudgetPolicy.php
- view, create, update, delete, changeStatus, approve
```

---

## 📊 Transições de Status (Enum BudgetStatus)

### Transições Válidas

```php
DRAFT     → PENDING, CANCELLED
PENDING   → APPROVED, REJECTED, CANCELLED, EXPIRED
APPROVED  → COMPLETED, CANCELLED
REJECTED  → DRAFT (reabrir)
CANCELLED → DRAFT (reabrir)
EXPIRED   → DRAFT (reabrir)
COMPLETED → (status final)
```

### Métodos do Enum Utilizados

```php
BudgetStatus::canTransitionTo(BudgetStatus $target) // Validação
BudgetStatus::getActive()                           // Status ativos
BudgetStatus::getFinished()                         // Status finalizados
BudgetStatus::getOptions()                          // Para selects
BudgetStatus::calculateMetrics()                    // Dashboards
```

---

## 🔧 Implementação Passo a Passo

### Fase 1: Fundamentos (Semanas 1-2)

#### Semana 1: Models e Migrations

-  [ ] Criar migration `budgets` com campo `status` (enum)
-  [ ] Atualizar model `Budget` com enum casting
-  [ ] Implementar traits `TenantScoped` e `Auditable`
-  [ ] Criar relacionamentos (customer, services, etc.)

#### Semana 2: Repositories

-  [ ] Implementar `BudgetRepository` com métodos CRUD
-  [ ] Criar `ServiceRepository` para serviços vinculados
-  [ ] Implementar `CustomerRepository` para listagem
-  [ ] Criar `UserConfirmationTokenRepository`

### Fase 2: Services (Semanas 3-4)

#### Semana 3: Services Core

-  [ ] `BudgetService` - Lógica principal CRUD
-  [ ] `BudgetCodeGeneratorService` - Códigos únicos com lock
-  [ ] `BudgetTokenService` - Gestão de tokens públicos

#### Semana 4: Services Especializados

-  [ ] `BudgetStatusService` - Controle de status e transições
-  [ ] `BudgetPdfService` - Geração de PDFs com hash

### Fase 3: Controllers (Semanas 5-6)

#### Semana 5: Provider Controller

-  [ ] `BudgetController` - Métodos CRUD (index, create, store, etc.)
-  [ ] Implementar validações via Form Requests
-  [ ] Activity logging automático

#### Semana 6: Public Controller

-  [ ] `PublicBudgetController` - Aprovação por cliente
-  [ ] Validação de tokens públicos
-  [ ] Processamento de aprovação/rejeição

### Fase 4: Views e Events (Semanas 7-8)

#### Semana 7: Views Blade

-  [ ] Templates para CRUD completo
-  [ ] Formulários com validação client-side
-  [ ] Interface para escolha de status (cliente)

#### Semana 8: Events e Notifications

-  [ ] Implementar events para mudanças de status
-  [ ] Criar listeners para notificações por email
-  [ ] Sistema de templates de email

### Fase 5: Testes e Otimização (Semanas 9-10)

#### Semana 9: Testes

-  [ ] Testes unitários para services
-  [ ] Testes de feature para controllers
-  [ ] Testes de transição de status

#### Semana 10: Otimização

-  [ ] Cache inteligente para queries frequentes
-  [ ] Otimização de eager loading
-  [ ] Performance tuning

---

## ⚠️ Pontos Críticos de Atenção

### 1. **Geração de Código Único**

```php
// Implementar com lock para evitar duplicatas
$code = $this->codeGenerator->generateUniqueCode($tenantId, 'ORC');
```

### 2. **Controle de Transições de Status**

```php
// Usar métodos do enum ao invés de consultas
if (!$currentStatus->canTransitionTo($newStatus)) {
    throw new InvalidStatusTransitionException();
}
```

### 3. **Mudança em Cascata**

```php
// Orçamento + serviços devem mudar status juntos
DB::transaction(function() use ($budget, $newStatus) {
    $budget->update(['status' => $newStatus]);
    $budget->services()->update(['status' => $newStatus]);
});
```

### 4. **Sistema de Tokens**

```php
// Tokens com expiração automática
$token = UserConfirmationToken::create([
    'user_id' => $budget->customer->user_id,
    'type' => 'budget_approval',
    'expires_at' => now()->addHours(24)
]);
```

### 5. **PDF com Hash de Verificação**

```php
// Gerar hash único na primeira geração
$hash = hash('sha256', $budget->id . $budget->updated_at . config('app.key'));
$budget->update(['pdf_verification_hash' => $hash]);
```

---

## 📋 Checklist Final de Implementação

### Models e Database

-  [ ] Migration `budgets` com campo `status` (string)
-  [ ] Model `Budget` com enum casting
-  [ ] Relacionamentos corretos (customer, services, etc.)
-  [ ] Traits `TenantScoped` e `Auditable`

### Services Layer

-  [ ] `BudgetService` - CRUD e lógica de negócio
-  [ ] `BudgetCodeGeneratorService` - Códigos únicos
-  [ ] `BudgetStatusService` - Controle de status
-  [ ] `BudgetPdfService` - Geração de PDFs
-  [ ] `BudgetTokenService` - Gestão de tokens

### Controllers

-  [ ] `BudgetController` (provider) - 8 métodos
-  [ ] `PublicBudgetController` (cliente) - 2 métodos
-  [ ] Form Requests com validações
-  [ ] Policies de autorização

### Views e Frontend

-  [ ] Templates Blade para CRUD
-  [ ] Formulários responsivos
-  [ ] Interface de aprovação para cliente
-  [ ] JavaScript para interatividade
-  [ ] **Integração Vanilla JavaScript** (após CustomerController)
-  [ ] **Máscaras automáticas** para campos específicos
-  [ ] **Validações frontend** para dados financeiros

### Events e Notifications

-  [ ] Events para mudanças de status
-  [ ] Listeners para notificações
-  [ ] Templates de email
-  [ ] Queue para processamento assíncrono

### Segurança e Validação

-  [ ] Rate limiting em endpoints públicos
-  [ ] Validação de tokens com expiração
-  [ ] Autorização baseada em policies
-  [ ] Sanitização de inputs

### Testes e Qualidade

-  [ ] Testes unitários (80% cobertura)
-  [ ] Testes de feature para controllers
-  [ ] Testes de transição de status
-  [ ] Testes de geração de PDF

---

## 🎯 Resultado Esperado

Após implementação completa:

1. **Funcionalidade Completa:** Todos os 12 métodos do sistema legado migrados
2. **Arquitetura Moderna:** Padrões Laravel (Controller → Services → Repositories → Models)
3. **Controle de Status Robusto:** Enum com validações e transições controladas
4. **Performance Otimizada:** Eager loading, cache e queries eficientes
5. **Segurança Avançada:** Autorização, validação e auditoria completas
6. **Interface Moderna:** Blade templates responsivos e funcionais
7. **Testabilidade:** Cobertura de testes adequada
8. **Manutenibilidade:** Código limpo e bem estruturado

---

**Status Atual:** 📝 Planejamento completo realizado
**Próximo Passo:** Iniciar implementação da Fase 1 (Models e Migrations)

**Responsável:** Equipe de Desenvolvimento
**Data de Início:** A definir
**Data de Conclusão Estimada:** 10 semanas
