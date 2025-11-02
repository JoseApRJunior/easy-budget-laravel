# Relatório de Análise - ServiceController (Sistema Antigo)

## 📋 Sumário Executivo

Análise completa do `ServiceController` do sistema antigo para migração ao Laravel 12.

**Arquivo:** `old-system/app/controllers/ServiceController.php`  
**Data:** 2025  
**Objetivo:** Mapear funcionalidades, dependências e fluxos para implementação no novo sistema.

---

## 🎯 Visão Geral

### Dependências Injetadas (14 total)

1. **Twig** - Template engine
2. **Budget** - Model de orçamentos
3. **Sanitize** - Sanitização
4. **ServiceService** - Lógica de negócio
5. **Service** - Model de serviços
6. **ServiceItem** - Model de itens
7. **Product** - Model de produtos
8. **Category** - Model de categorias
9. **Invoice** - Model de faturas
10. **ActivityService** - Logs
11. **Unit** - Model de unidades
12. **Schedule** - Model de agendamentos
13. **Customer** - Model de clientes
14. **SharedService** - Serviços compartilhados

---

## 📊 Métodos (13 total)

### 1. `index()` - Lista de Serviços
- **Rota:** GET `/provider/services`
- **View:** `pages/service/index.twig`
- **Função:** Exibe listagem de serviços

### 2. `create($code)` - Formulário de Criação
- **Rota:** GET `/provider/services/create/{code?}`
- **View:** `pages/service/create.twig`
- **Dados:**
  - Categorias: `$this->category->getAllCategories()`
  - Unidades: `$this->unit->findAll()`
  - Produtos ativos: `$this->product->getAllProductsActive()`
  - Orçamentos não concluídos: `$this->budget->getAllBudgetsNotCompleted()`
  - Código do orçamento (opcional)

### 3. `store()` - Criar Serviço
- **Rota:** POST `/provider/services`
- **Validação:** `ServiceFormRequest::validate()`
- **Lógica:**
  1. Valida dados
  2. Cria serviço via `$this->serviceService->createService()`
  3. Registra atividade: `service_created`
- **Redirect:** `/provider/services/show/{code}` (sucesso)

### 4. `show($code)` - Detalhes do Serviço
- **Rota:** GET `/provider/services/show/{code}`
- **View:** `pages/service/show.twig`
- **Dados:**
  - Serviço completo: `$this->service->getServiceFullByCode()`
  - Orçamento vinculado com dados do cliente
  - Itens do serviço
  - Último agendamento
  - Fatura vinculada (se existir)

### 5. `change_status()` - Alterar Status (Provider)
- **Rota:** POST `/provider/services/change-status`
- **Validação:** `ServiceChangeStatusFormRequest::validate()`
- **Lógica:**
  1. Delega para `$this->serviceService->handleStatusChange()`
  2. Pode alterar status do orçamento vinculado
  3. Registra atividade: `service_status_changed`
- **Redirect:** `/provider/services/show/{code}`

### 6. `update($code)` - Formulário de Edição
- **Rota:** GET `/provider/services/update/{code}`
- **View:** `pages/service/update.twig`
- **Dados:**
  - Serviço completo
  - Itens do serviço
  - Categorias, unidades, produtos
  - Orçamentos não concluídos

### 7. `update_store()` - Atualizar Serviço
- **Rota:** POST `/provider/services/update`
- **Validação:** `ServiceFormRequest::validate()`
- **Lógica:**
  1. Valida dados
  2. Atualiza via `$this->serviceService->updateService()`
  3. Registra atividade: `service_updated`
- **Redirect:** `/provider/services/show/{code}` (sucesso)

### 8. `view_service_status($code, $token)` - Visualização Cliente
- **Rota:** GET `/services/view-service-status/code/{code}/token/{token}`
- **View:** `pages/service/view_service_status.twig`
- **Lógica de Token:**
  1. Valida token via `SharedService->validateUserConfirmationToken()`
  2. Se expirado: gera novo e envia email
  3. Valida se token pertence ao agendamento
  4. Valida data de validade do serviço
  5. Carrega dados completos

### 9. `choose_service_status_store()` - Salvar Status (Cliente)
- **Rota:** POST `/services/choose-service-status`
- **Validação:** `ServiceChooseStatusFormRequest::validate()`
- **Lógica:**
  1. Valida token
  2. Processa mudança de status
  3. Registra atividade
- **Redirect:** `/services/view-service-status/code/{code}/token/{token}`

### 10. `delete_store($code)` - Deletar Serviço
- **Rota:** POST `/provider/services/delete/{code}`
- **Lógica:**
  1. Busca serviço por código
  2. Deleta todos os itens do serviço (loop)
  3. Deleta o serviço
  4. Registra atividade: `service_deleted`
- **Redirect:** `/provider/services`

### 11. `cancel($code)` - Cancelar Serviço
- **Rota:** POST `/provider/services/cancel/{code}`
- **Lógica:**
  1. Busca serviço
  2. Atualiza status para CANCELLED (id=9)
  3. Registra atividade: `service_updated`
- **Redirect:** `/provider/services/show/{code}`

### 12. `print($code, $token)` - Gerar PDF
- **Rota:** GET `/services/print/{code}/{token?}`
- **Lógica:**
  1. Valida token (se fornecido)
  2. Busca serviço + orçamento + cliente
  3. Busca itens + agendamento
  4. Valida token vs agendamento
  5. Gera PDF via `ServiceService->printPDF()`
- **Response:** PDF inline

### 13. `activityLogger()` - Helper de Log
- **Função:** Registra atividades no sistema

---

## 📦 Estrutura de Dados

### ServiceEntity (Campos)
```
id, tenant_id, budget_id, code, category_id, service_statuses_id,
due_date, total, description, created_at, updated_at
```

### ServiceItemEntity (Campos)
```
id, tenant_id, service_id, product_id, quantity, unit_price, total
```

### ScheduleEntity (Campos)
```
id, tenant_id, service_id, user_confirmation_token_id,
start_date_time, end_date_time, location
```

---

## 🔄 Fluxos de Negócio

### Fluxo 1: Criação de Serviço
1. Provider acessa formulário
2. Seleciona orçamento (ou vem pré-selecionado)
3. Seleciona categoria
4. Adiciona produtos/itens
5. Sistema gera código: `{BUDGET_CODE}-S001`
6. **Transação:**
   - Cria Service
   - Cria ServiceItems (loop)
   - Atualiza total do Budget
7. Registra atividade
8. Redirect para detalhes

### Fluxo 2: Mudança de Status (Provider)
1. Provider visualiza serviço
2. Seleciona novo status
3. Sistema valida transição
4. Atualiza serviço
5. Pode atualizar orçamento vinculado
6. Registra atividade

### Fluxo 3: Visualização por Cliente (Token)
1. Cliente recebe email com token
2. Acessa link público
3. Sistema valida token
4. Valida se token pertence ao agendamento
5. Valida data de validade
6. Cliente visualiza status
7. Cliente pode atualizar status (se permitido)

### Fluxo 4: Atualização de Serviço
1. Provider acessa formulário
2. Modifica dados/itens
3. **Transação:**
   - Atualiza Service
   - Deleta itens removidos
   - Atualiza itens modificados
   - Cria novos itens
   - Atualiza total do Budget
4. Registra atividade

### Fluxo 5: Exclusão de Serviço
1. Provider solicita exclusão
2. Sistema deleta todos os itens (loop)
3. Sistema deleta o serviço
4. Registra atividade

---

## 🔧 ServiceService (Métodos Principais)

### 1. `createService(array $data)`
- Cria serviço vinculado a orçamento
- Gera código único: `{BUDGET_CODE}-S{SEQ}`
- Cria itens do serviço
- Atualiza total do orçamento

### 2. `updateService(array $data)`
- Atualiza serviço
- Gerencia itens (delete/update/create)
- Atualiza total do orçamento

### 3. `handleStatusChange(array $data, $authenticated)`
- Gerencia mudança de status
- Pode atualizar orçamento vinculado
- Validações de transição

### 4. `printPDF(...)`
- Gera PDF do serviço
- Inclui dados do cliente, orçamento, itens

### 5. `handleTokenUpdateScheduledStatus($code, $token, $authenticated)`
- Atualiza token expirado
- Envia novo email

---

## ⚠️ Pontos Críticos

### 1. Geração de Código Único
```php
$last_code = $this->service->getLastCode($budget_id, $tenant_id);
$last_code = (float)(substr($last_code, -3)) + 1;
$code = $budget_code . '-S' . str_pad((string)$last_code, 3, '0', STR_PAD_LEFT);
```
**Formato:** `ORC-20250101-S001`

### 2. Sistema de Tokens com Agendamento
- Token vinculado ao agendamento (não ao serviço)
- Validação: `schedule->user_confirmation_token_id === token->id`
- Expiração automática
- Regeneração quando expirado

### 3. Mudança de Status em Cascata
- Serviço pode alterar status do orçamento
- Validações de transição complexas

### 4. Exclusão em Cascata
- Deleta itens antes de deletar serviço
- Não verifica relacionamentos (agendamentos, faturas)

### 5. Validação de Data de Validade
```php
if (new DateTime() > convertToDateTime($service->due_date)) {
    return error('A data de validade deste serviço expirou.');
}
```

---

## 📝 Validações (ServiceFormRequest)

### Campos Obrigatórios
- budget_id (código do orçamento)
- category (categoria do serviço)
- status (status inicial)
- description (descrição)
- items (array de produtos/itens)
  - id (product_id)
  - quantity
  - unit_price
  - total

---

## 📝 Recomendações Laravel

### Models
```php
Service (belongsTo: Budget, Category, ServiceStatus)
Service (hasMany: ServiceItem, Schedule, Invoice)
ServiceItem (belongsTo: Service, Product)
Schedule (belongsTo: Service, UserConfirmationToken)
```

### Controllers
```php
ServiceController (provider - CRUD)
PublicServiceController (cliente - visualização/status)
```

### Services
```php
ServiceService - Lógica de negócio
ServiceCodeGeneratorService - Códigos únicos
ServiceStatusService - Gerenciamento de status
ServicePdfService - Geração de PDFs
ServiceTokenService - Gestão de tokens
```

### Form Requests
```php
ServiceStoreRequest
ServiceUpdateRequest
ServiceChangeStatusRequest
ServiceChooseStatusRequest
```

### Events & Listeners
```php
ServiceCreated → SendServiceCreatedNotification
ServiceStatusChanged → SendServiceStatusNotification
ServiceScheduled → SendServiceScheduledNotification
ServiceCompleted → SendServiceCompletedNotification
```

### Policies
```php
ServicePolicy:
- view, create, update, delete, changeStatus, cancel
```

---

## 🔄 Status de Serviço (Transições)

- **DRAFT** → PENDING
- **PENDING** → SCHEDULING
- **SCHEDULING** → SCHEDULED
- **SCHEDULED** → IN_PROGRESS
- **IN_PROGRESS** → COMPLETED
- **IN_PROGRESS** → PARTIAL
- **Qualquer** → CANCELLED
- **Qualquer** → NOT_PERFORMED
- **Qualquer** → EXPIRED

---

## ✅ Checklist de Implementação

- [ ] Criar migration de services
- [ ] Criar migration de service_items
- [ ] Criar migration de schedules
- [ ] Criar models com relationships
- [ ] Criar ServiceService
- [ ] Criar ServiceController (provider)
- [ ] Criar PublicServiceController (cliente)
- [ ] Implementar geração de código único
- [ ] Implementar sistema de tokens com agendamento
- [ ] Implementar mudança de status
- [ ] Implementar exclusão em cascata
- [ ] Implementar geração de PDF
- [ ] Criar Form Requests
- [ ] Criar Events & Listeners
- [ ] Criar Policies
- [ ] Criar views Blade
- [ ] Implementar testes
- [ ] Adicionar verificação de relacionamentos antes de deletar

---

## 🐛 Bugs/Melhorias Identificados

### 1. Exclusão sem Verificação
**Problema:** Deleta serviço sem verificar agendamentos/faturas vinculados
**Solução:** Implementar `checkRelationships()` antes de deletar

### 2. Cancelamento Direto
**Problema:** Método `cancel()` atualiza status diretamente (id=9)
**Solução:** Usar `handleStatusChange()` para validações

### 3. TODO Pendente
**Localização:** `create()` linha ~60
```php
//TODO IMPLEMENTAR USO DE UNITS
```

---

**Fim do Relatório**
