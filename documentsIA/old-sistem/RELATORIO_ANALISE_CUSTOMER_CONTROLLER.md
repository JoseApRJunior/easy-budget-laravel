# Relatório de Análise - CustomerController (Sistema Antigo)

## 📋 Sumário Executivo

Análise completa do `CustomerController` do sistema antigo para migração ao Laravel 12.

**Arquivo:** `old-system/app/controllers/CustomerController.php`  
**Data:** 2025  
**Objetivo:** Mapear funcionalidades, dependências e fluxos para implementação no novo sistema.

---

## 🎯 Visão Geral

### Dependências Injetadas (8 total)

1. **Twig** - Template engine
2. **AreaOfActivity** - Model de áreas de atuação
3. **Profession** - Model de profissões
4. **Customer** - Model de clientes
5. **CustomerService** - Lógica de negócio
6. **Contact** - Model de contatos
7. **Sanitize** - Sanitização
8. **ActivityService** - Logs

---

## 📊 Métodos (7 total)

### 1. `index()` - Lista de Clientes
- **Rota:** GET `/provider/customers`
- **View:** `pages/customer/index.twig`
- **Função:** Exibe listagem de clientes

### 2. `create()` - Formulário de Criação
- **Rota:** GET `/provider/customers/create`
- **View:** `pages/customer/create.twig`
- **Dados:**
  - Áreas de atuação: `$this->areaOfActivity->findAll()`
  - Profissões: `$this->profession->findAll()`

### 3. `store()` - Criar Cliente
- **Rota:** POST `/provider/customers`
- **Validação:** `CustomerFormRequest::validate()`
- **Lógica:**
  1. Valida dados do formulário
  2. Verifica se email já existe: `$this->contact->getContactByEmail()`
  3. Se existe: retorna erro
  4. Cria cliente via `$this->customerService->create()`
  5. Registra atividade: `customer_created`
- **Redirect:** `/provider/customers/show/{id}` (sucesso)

### 4. `show($id)` - Detalhes do Cliente
- **Rota:** GET `/provider/customers/show/{id}`
- **View:** `pages/customer/show.twig`
- **Dados:** Cliente completo via `$this->customer->getCustomerFullById()`

### 5. `update($id)` - Formulário de Edição
- **Rota:** GET `/provider/customers/update/{id}`
- **View:** `pages/customer/update.twig`
- **Dados:**
  - Cliente completo
  - Áreas de atuação
  - Profissões

### 6. `update_store()` - Atualizar Cliente
- **Rota:** POST `/provider/customers/update`
- **Validação:** `CustomerFormRequest::validate()`
- **Lógica:**
  1. Valida dados
  2. Atualiza via `$this->customerService->update()`
  3. Registra atividade: `customer_updated`
- **Redirect:** `/provider/customers/show/{id}` (sucesso)

### 7. `delete_store($id)` - Deletar Cliente
- **Rota:** POST `/provider/customers/delete/{id}`
- **Lógica:**
  1. Verifica relacionamentos: `$this->customerService->checkRelationships()`
  2. Se houver orçamentos/serviços: impede exclusão
  3. Deleta cliente via `$this->customerService->delete()`
  4. Registra atividade: `customer_updated` (deveria ser customer_deleted)
- **Redirect:** `/provider/customers`

---

## 📦 Estrutura de Dados do Cliente

### Entidades Relacionadas (4 tabelas)

#### 1. CustomerEntity
```
id, tenant_id, common_data_id, contact_id, address_id, status
```

#### 2. CommonDataEntity
```
id, tenant_id, first_name, last_name, cpf, cnpj, birth_date,
company_name, area_of_activity_id, profession_id, website, description
```

#### 3. ContactEntity
```
id, tenant_id, email, email_business, phone, phone_business
```

#### 4. AddressEntity
```
id, tenant_id, cep, address, address_number, complement,
neighborhood, city, state
```

---

## 🔄 Fluxos de Negócio

### Fluxo 1: Criação de Cliente (Transação Complexa)
1. Provider acessa formulário
2. Preenche dados pessoais, contato e endereço
3. Sistema valida email único
4. **Transação inicia:**
   - Cria CommonData
   - Cria Contact
   - Cria Address
   - Cria Customer (vincula os 3 IDs)
5. **Transação commita**
6. Registra atividade
7. Redirect para detalhes

### Fluxo 2: Atualização de Cliente (Transação Complexa)
1. Provider acessa formulário de edição
2. Modifica dados
3. Sistema valida email (se mudou, verifica se não pertence a outro)
4. **Transação inicia:**
   - Atualiza Customer (se mudou)
   - Atualiza Contact (se mudou)
   - Atualiza Address (se mudou)
   - Atualiza CommonData (se mudou)
5. **Transação commita**
6. Registra atividade
7. Redirect para detalhes

### Fluxo 3: Exclusão de Cliente
1. Provider solicita exclusão
2. Sistema verifica relacionamentos (budgets, services)
3. Se houver: impede exclusão com mensagem
4. Se não houver:
   - **Transação inicia:**
     - Deleta Customer
     - Deleta CommonData
     - Deleta Contact
     - Deleta Address
   - **Transação commita**
5. Registra atividade
6. Redirect para listagem

---

## 🔧 CustomerService (Métodos)

### 1. `create(array $data)` - Criar Cliente
- **Transação:** Sim
- **Lógica:**
  1. Cria CommonData
  2. Cria Contact
  3. Cria Address
  4. Cria Customer (vincula os 3)
- **Retorno:** Array com status, message, data (IDs criados)

### 2. `update(array $data)` - Atualizar Cliente
- **Transação:** Sim
- **Validações:**
  - Verifica se email mudou e se já existe
  - Compara dados originais vs novos (só atualiza se mudou)
- **Lógica:**
  1. Atualiza Customer
  2. Atualiza Contact
  3. Atualiza Address
  4. Atualiza CommonData
- **Retorno:** Array com status, message, data

### 3. `delete(int $id)` - Deletar Cliente
- **Transação:** Sim
- **Lógica:**
  1. Busca cliente
  2. Deleta Customer
  3. Deleta CommonData
  4. Deleta Contact
  5. Deleta Address
- **Retorno:** Array com status, message, data

### 4. `checkRelationships($tableId, $tenantId)` - Verificar Relacionamentos
- **Função:** Verifica se cliente tem orçamentos ou serviços vinculados
- **Lógica:**
  1. Consulta INFORMATION_SCHEMA
  2. Busca foreign keys apontando para customers
  3. Conta registros relacionados
- **Retorno:** Array com hasRelationships, table, count, records

---

## ⚠️ Pontos Críticos

### 1. Estrutura Multi-Tabela
- Cliente dividido em 4 tabelas relacionadas
- Criação/atualização/exclusão em cascata
- Transações obrigatórias para manter integridade

### 2. Validação de Email Único
```php
$checkObj = $this->contact->getContactByEmail($data['email'], $tenant_id);
if (!$checkObj instanceof EntityNotFound) {
    // Email já existe
}
```

### 3. Comparação de Dados (Otimização)
- Só atualiza se dados mudaram
- Usa função `compareObjects()` para verificar

### 4. Verificação de Relacionamentos
- Antes de deletar: verifica budgets e services
- Impede exclusão se houver dependências

### 5. Bug no Log de Exclusão
```php
// Deveria ser 'customer_deleted' mas está 'customer_updated'
$this->activityLogger(..., 'customer_updated', ...);
```

---

## 📝 Validações (CustomerFormRequest)

### Campos Obrigatórios

#### Dados Pessoais
- first_name, last_name
- email (formato válido)
- email_business (formato válido)
- phone, phone_business
- birth_date (formato Y-m-d)

#### Dados Empresariais
- company_name
- cpf (obrigatório)
- cnpj (opcional)
- area_of_activity_id
- profession_id
- website (opcional, formato URL)

#### Endereço
- cep, address, address_number
- neighborhood, city, state
- complement (opcional)
- description (opcional)

---

## 📝 Recomendações Laravel

### Models
```php
Customer (hasOne: CommonData, Contact, Address)
CommonData (belongsTo: Customer, AreaOfActivity, Profession)
Contact (belongsTo: Customer)
Address (belongsTo: Customer)
```

### Controllers
```php
CustomerController (provider - CRUD completo)
```

### Services
```php
CustomerService - Lógica de negócio
CustomerRelationshipService - Verificação de relacionamentos
```

### Form Requests
```php
CustomerStoreRequest
CustomerUpdateRequest
```

### Events & Listeners
```php
CustomerCreated → SendCustomerCreatedNotification
CustomerUpdated → SendCustomerUpdatedNotification
CustomerDeleted → SendCustomerDeletedNotification
```

### Policies
```php
CustomerPolicy:
- view, create, update, delete
```

---

## 🔄 Migração para Laravel

### Opção 1: Manter Estrutura Multi-Tabela
- Usar Eloquent relationships
- Transactions com DB::transaction()
- Observers para cascata

### Opção 2: Simplificar (Recomendado)
- Consolidar em 1-2 tabelas
- customers (dados principais)
- customer_addresses (múltiplos endereços)
- Usar JSON para dados flexíveis

### Estrutura Sugerida
```php
customers:
- id, tenant_id, first_name, last_name
- email, email_business, phone, phone_business
- cpf, cnpj, birth_date, company_name
- area_of_activity_id, profession_id
- website, description, status
- created_at, updated_at

customer_addresses:
- id, customer_id, tenant_id
- cep, address, number, complement
- neighborhood, city, state
- is_primary
- created_at, updated_at
```

---

## ✅ Checklist de Implementação

- [ ] Decidir estrutura de tabelas (multi vs consolidada)
- [ ] Criar migrations
- [ ] Criar models com relationships
- [ ] Criar CustomerService
- [ ] Criar CustomerController
- [ ] Implementar validação de email único
- [ ] Implementar verificação de relacionamentos
- [ ] Criar Form Requests
- [ ] Criar Events & Listeners
- [ ] Criar Policies
- [ ] Criar views Blade
- [ ] Implementar testes
- [ ] Corrigir bug do log de exclusão

---

## 🐛 Bugs Identificados

### 1. Log de Exclusão Incorreto
**Localização:** `delete_store()` linha ~220
```php
// ERRADO
'customer_updated'

// CORRETO
'customer_deleted'
```

---

**Fim do Relatório**
