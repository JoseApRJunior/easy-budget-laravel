# Relatório de Análise - BudgetController (Sistema Antigo)

## 📋 Sumário Executivo

Análise completa do `BudgetController` do sistema antigo para migração ao Laravel 12.

**Arquivo:** `old-system/app/controllers/BudgetController.php`  
**Data:** 2025  
**Objetivo:** Mapear funcionalidades, dependências e fluxos para implementação no novo sistema.

---

## 🎯 Visão Geral

### Dependências Injetadas (11 total)

1. **Twig** - Template engine
2. **Customer** - Model de clientes
3. **Budget** - Model de orçamentos
4. **Service** - Model de serviços
5. **ServiceItem** - Model de itens
6. **BudgetService** - Lógica de negócio
7. **Sanitize** - Sanitização
8. **ActivityService** - Logs
9. **SharedService** - Serviços compartilhados
10. **Schedule** - Agendamentos
11. **Request** - HTTP Request

---

## 📊 Métodos (12 total)

### 1. `index()` - Lista de Orçamentos
- **Rota:** GET `/provider/budgets`
- **View:** `pages/budget/index.twig`
- **Função:** Exibe listagem de orçamentos

### 2. `create()` - Formulário de Criação
- **Rota:** GET `/provider/budgets/create`
- **View:** `pages/budget/create.twig`
- **Dados:** Lista de clientes do tenant
- **Chamadas:** `$this->customer->getAllCustomers($tenant_id)`

### 3. `store()` - Criar Orçamento
- **Rota:** POST `/provider/budgets`
- **Validação:** `BudgetFormRequest::validate()`
- **Lógica:**
  1. Valida dados do formulário
  2. Gera código único: `ORC-YYYYMMDD0001`
  3. Define status inicial: `budget_statuses_id = 1`
  4. Cria entidade `BudgetEntity`
  5. Salva no banco via `$this->budget->create()`
  6. Registra atividade: `budget_created`
- **Redirect:** `/provider/budgets` (sucesso)

### 4. `update($code)` - Formulário de Edição
- **Rota:** GET `/provider/budgets/update/{code}`
- **View:** `pages/budget/update.twig`
- **Dados:** Orçamento completo + Serviços vinculados
- **Chamadas:**
  - `$this->budget->getBudgetFullByCode($code, $tenant_id)`
  - `$this->service->getAllServiceFullByIdBudget($budget_id, $tenant_id)`

### 5. `update_store()` - Atualizar Orçamento
- **Rota:** POST `/provider/budgets/update`
- **Validação:** `BudgetFormRequest::validate()`
- **Lógica:**
  1. Valida dados
  2. Busca orçamento existente
  3. Valida data de vencimento (não pode ser anterior a hoje)
  4. Compara dados originais vs novos
  5. Atualiza apenas se houver mudanças
  6. Registra atividade: `budget_updated` (com before/after)
- **Redirect:** `/provider/budgets/show/{code}` (sucesso)

### 6. `show($code)` - Detalhes do Orçamento
- **Rota:** GET `/provider/budgets/show/{code}`
- **View:** `pages/budget/show.twig`
- **Dados Complexos:**
  - Orçamento com dados do cliente
  - Todos os serviços vinculados
  - Itens de cada serviço
  - Último agendamento de cada serviço
  - Flag: `all_services_completed`

### 7. `change_status()` - Alterar Status (Provider)
- **Rota:** POST `/provider/budgets/change-status`
- **Validação:** `BudgetChangeStatusFormRequest::validate()`
- **Lógica:** Delega para `BudgetService->handleStatusChange()`
- **Atualiza:** Orçamento + Serviços vinculados em cascata

### 8. `choose_budget_status($code, $token)` - Escolha de Status (Cliente)
- **Rota:** GET `/budgets/choose-budget-status/code/{code}/token/{token}`
- **View:** `pages/budget/choose_budget_status.twig`
- **Lógica de Token:**
  1. Valida token via `SharedService->validateUserConfirmationToken()`
  2. Se expirado: gera novo token e envia email
  3. Carrega dados do orçamento + serviços + itens

### 9. `choose_budget_status_store()` - Salvar Escolha (Cliente)
- **Rota:** POST `/budgets/choose-budget-status`
- **Validação:** `BudgetChooseStatusFormRequest::validate()`
- **Lógica:** Valida token + Processa aprovação/rejeição

### 10. `print($code, $token)` - Gerar PDF
- **Rota:** GET `/budgets/print/{code}/{token?}`
- **Lógica:**
  1. Valida token (se fornecido)
  2. Busca orçamento + cliente + serviços + itens
  3. Gera hash de verificação (se não existir)
  4. Cria PDF via `BudgetService->printPDF()`
- **Response:** PDF inline (Content-Type: application/pdf)

### 11. `delete_store($code)` - Deletar Orçamento
- **Rota:** POST `/provider/budgets/delete/{code}`
- **Lógica:**
  1. Busca orçamento por código
  2. Verifica relacionamentos: `Budget->checkRelationships()`
  3. Se houver serviços: impede exclusão
  4. Deleta orçamento
  5. Registra atividade: `budget_deleted`

### 12. `activityLogger()` - Helper de Log
- **Tipo:** Helper interno
- **Função:** Registra atividades no sistema
- **Parâmetros:** tenant_id, user_id, action_type, entity_type, entity_id, description, metadata

---

## 📦 Entidade Budget (Campos)

```
id, tenant_id, customer_id, code, budget_statuses_id,
user_confirmation_token_id, due_date, discount, total,
description, payment_terms, attachment, history,
pdf_verification_hash, created_at, updated_at
```

---

## 🔄 Fluxos de Negócio

### Fluxo 1: Criação de Orçamento
1. Provider acessa formulário
2. Seleciona cliente
3. Preenche dados básicos
4. Sistema gera código único
5. Status inicial: DRAFT
6. Salva no banco
7. Registra atividade

### Fluxo 2: Mudança de Status (Provider)
1. Provider visualiza orçamento
2. Seleciona novo status
3. Sistema valida transição
4. Atualiza orçamento + serviços
5. Registra atividade

### Fluxo 3: Aprovação por Cliente
1. Cliente recebe email com token
2. Acessa link público
3. Sistema valida token
4. Cliente escolhe: Aprovar/Rejeitar
5. Sistema atualiza status
6. Registra atividade

### Fluxo 4: Geração de PDF
1. Valida permissões/token
2. Busca dados completos
3. Gera hash de verificação
4. Cria PDF
5. Retorna inline

---

## ⚠️ Pontos Críticos

### 1. Geração de Código Único
```php
$last_code = $this->budget->getLastCode($tenant_id);
$last_code = (float)(substr($last_code, -4)) + 1;
$code = 'ORC-' . date('Ymd') . str_pad((string)$last_code, 4, '0', STR_PAD_LEFT);
```
**Ação:** Implementar com lock para evitar duplicatas

### 2. Sistema de Tokens
- Expiração automática
- Regeneração quando expirado
- Envio de email com novo token

### 3. Mudança de Status em Cascata
- Orçamento altera status de serviços vinculados
- Validação de transições permitidas

### 4. Verificação de Relacionamentos
- Antes de deletar: verifica serviços
- Impede exclusão se houver dependências

### 5. PDF com Hash
- Gera hash único na primeira geração
- Usa para validação de autenticidade

---

## 📝 Recomendações Laravel

### Controllers
```php
BudgetController (provider - CRUD)
PublicBudgetController (cliente - aprovação)
```

### Services
```php
BudgetService - Lógica de negócio
BudgetCodeGeneratorService - Códigos únicos
BudgetStatusService - Gerenciamento de status
BudgetPdfService - Geração de PDFs
BudgetTokenService - Gestão de tokens
```

### Form Requests
```php
BudgetStoreRequest
BudgetUpdateRequest
BudgetChangeStatusRequest
BudgetApprovalRequest
```

### Events & Listeners
```php
BudgetCreated → SendBudgetCreatedNotification
BudgetStatusChanged → SendBudgetStatusNotification
BudgetApproved → SendBudgetApprovedNotification
BudgetRejected → SendBudgetRejectedNotification
```

### Policies
```php
BudgetPolicy:
- view, create, update, delete, changeStatus
```

---

## 📊 Status de Orçamento (Transições)

- **DRAFT** → PENDING (quando tem serviços)
- **PENDING** → APPROVED (aprovado)
- **PENDING** → REJECTED (rejeitado)
- **CANCELLED/REJECTED/EXPIRED** → DRAFT (reabrir)
- **Qualquer** → CANCELLED (cancelar)
- **APPROVED** → COMPLETED (todos serviços finalizados)
- **Qualquer** → EXPIRED (expirar)

---

## ✅ Checklist de Implementação

- [ ] Criar migration de budgets
- [ ] Criar model Budget com relationships
- [ ] Criar BudgetService
- [ ] Criar BudgetController (provider)
- [ ] Criar PublicBudgetController (cliente)
- [ ] Implementar geração de código único
- [ ] Implementar sistema de tokens
- [ ] Implementar mudança de status
- [ ] Implementar geração de PDF
- [ ] Criar Form Requests
- [ ] Criar Events & Listeners
- [ ] Criar Policies
- [ ] Criar views Blade
- [ ] Implementar testes

---

## 🔧 BudgetService (Métodos do Sistema Antigo)

1. `handleStatusChange()` - Gerencia mudança de status
2. `changeStatus()` - Executa mudança com transação
3. `changeStatusBudget()` - Altera apenas status do orçamento
4. `createService()` - Cria serviço vinculado
5. `updateService()` - Atualiza serviço
6. `printPDF()` - Gera PDF
7. `handleTokenUpdateBudget()` - Atualiza token expirado
8. `sendNotificationBudget()` - Envia notificação

---

**Fim do Relatório**
