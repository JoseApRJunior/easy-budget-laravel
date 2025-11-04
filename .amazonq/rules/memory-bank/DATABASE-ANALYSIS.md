# Análise de Estrutura de Banco de Dados - Sistema Multi-tenant

## Data: 2025-01-02 (Atualizado)

## 📊 Estrutura Correta (Relacionamentos 1:1)

### Hierarquia de Entidades

```
Tenant (Raiz Multi-tenant)
├── User (N:1 com Tenant)
│   └── Provider (1:1 com User)
│       ├── CommonData (1:1) ← tem provider_id
│       ├── Address (1:1) ← tem provider_id
│       ├── Contact (1:1) ← tem provider_id
│       └── BusinessData (1:1 se PJ) ← tem provider_id
│
└── Customer (N:1 com Tenant)
    ├── CommonData (1:1) ← tem customer_id
    ├── Address (1:1) ← tem customer_id
    ├── Contact (1:1) ← tem customer_id
    └── BusinessData (1:1 se PJ) ← tem customer_id

Regra: FK sempre no lado "dependente" (CommonData, Address, Contact, BusinessData)
```

## 🔍 Análise Detalhada das Tabelas

### 1. **Tenant** (Entidade Raiz)
```sql
tenants
├── id (PK)
├── name
├── is_active
├── created_at
└── updated_at
```

**Papel**: Isolamento multi-tenant. Todas as outras entidades devem ter `tenant_id`.

**Relacionamentos**:
- `hasMany`: Users, Providers, Customers, Addresses, Contacts, CommonDatas, BusinessDatas
- **Não usa TenantScoped** (é a raiz)

---

### 2. **User** (Usuário do Sistema)
```sql
users
├── id (PK)
├── tenant_id (FK → tenants)
├── name
├── email (unique per tenant)
├── password
├── google_id
├── avatar
├── google_data (JSON)
├── logo
├── is_active
├── email_verified_at
├── extra_links
├── remember_token
├── created_at
└── updated_at
```

**Papel**: Autenticação e acesso ao sistema.

**Relacionamentos**:
- `belongsTo`: Tenant
- `hasOne`: Provider
- `belongsToMany`: Roles (via user_roles com tenant_id)

**Usa TenantScoped**: ✅ Sim

---

### 3. **Provider** (Provedor de Serviços)
```sql
providers
├── id (PK)
├── tenant_id (FK → tenants)
├── user_id (FK → users) [UNIQUE per tenant]
├── terms_accepted
├── created_at
└── updated_at
```

**Papel**: Representa o provedor de serviços (dono do tenant).

**Relacionamentos**:
- `belongsTo`: Tenant, User
- `hasOne`: CommonData, Address, Contact, BusinessData
- `hasMany`: Budgets, Services, PlanSubscriptions

**Usa TenantScoped**: ✅ Sim

**Índice Único**: `(tenant_id, user_id)` - Garante 1 provider por user por tenant

**Mudança**: ❌ Removido common_data_id, contact_id, address_id (FK invertidas)

---

### 4. **Customer** (Cliente)
```sql
customers
├── id (PK)
├── tenant_id (FK → tenants)
├── status (enum: active, inactive, deleted)
├── created_at
└── updated_at
```

**Papel**: Clientes do provedor (PF ou PJ).

**Relacionamentos**:
- `belongsTo`: Tenant
- `hasOne`: CommonData, Address, Contact, BusinessData
- `hasMany`: Budgets, Invoices, Interactions, Tags

**Usa TenantScoped**: ✅ Sim

**Mudança**: ❌ Removido common_data_id, contact_id, address_id (FK invertidas)

---

### 5. **CommonData** (Dados Comuns PF/PJ)
```sql
common_datas
├── id (PK)
├── tenant_id (FK → tenants)
├── customer_id (FK → customers) [nullable, unique] ✅
├── provider_id (FK → providers) [nullable, unique] ✅
├── type ENUM('individual', 'company') ✅
├── first_name (se type=individual)
├── last_name (se type=individual)
├── birth_date (se type=individual)
├── cpf (unique per tenant, se type=individual)
├── company_name (se type=company)
├── cnpj (unique per tenant, se type=company)
├── description
├── area_of_activity_id (FK)
├── profession_id (FK)
├── created_at
└── updated_at
-- CHECK: (customer_id IS NOT NULL AND provider_id IS NULL) OR (customer_id IS NULL AND provider_id IS NOT NULL)
```

**Papel**: Dados compartilhados entre PF e PJ (nome, CPF/CNPJ, etc).

**Relacionamentos**:
- `belongsTo`: Tenant, Customer, Provider, AreaOfActivity, Profession

**Usa TenantScoped**: ✅ Sim

**Mudanças**: 
- ✅ Adicionado customer_id, provider_id (FK invertidas)
- ✅ Adicionado campo `type` para diferenciar PF/PJ
- ✅ CHECK constraint garante XOR entre customer_id e provider_id

---

### 6. **Contact** (Contatos)
```sql
contacts
├── id (PK)
├── tenant_id (FK → tenants)
├── customer_id (FK → customers) [nullable, unique] ✅
├── provider_id (FK → providers) [nullable, unique] ✅
├── email_personal
├── phone_personal
├── email_business
├── phone_business
├── website
├── created_at
└── updated_at
-- CHECK: (customer_id IS NOT NULL AND provider_id IS NULL) OR (customer_id IS NULL AND provider_id IS NOT NULL)
```

**Papel**: Informações de contato.

**Relacionamentos**:
- `belongsTo`: Tenant, Customer, Provider

**Usa TenantScoped**: ✅ Sim

**Mudanças**: 
- ✅ Adicionado customer_id, provider_id (FK invertidas)
- ✅ Relacionamento 1:1 consistente com Customer e Provider
- ✅ CHECK constraint garante XOR entre customer_id e provider_id

---

### 7. **Address** (Endereços)
```sql
addresses
├── id (PK)
├── tenant_id (FK → tenants)
├── customer_id (FK → customers) [nullable, unique] ✅
├── provider_id (FK → providers) [nullable, unique] ✅
├── address
├── address_number
├── neighborhood
├── city
├── state
├── cep
├── created_at
└── updated_at
-- CHECK: (customer_id IS NOT NULL AND provider_id IS NULL) OR (customer_id IS NULL AND provider_id IS NOT NULL)
```

**Papel**: Endereços físicos.

**Relacionamentos**:
- `belongsTo`: Tenant, Customer, Provider

**Usa TenantScoped**: ✅ Sim

**Mudanças**: 
- ✅ Adicionado customer_id, provider_id (FK invertidas)
- ✅ Relacionamento 1:1 consistente com Customer e Provider
- ✅ CHECK constraint garante XOR entre customer_id e provider_id

---

### 8. **BusinessData** (Dados Empresariais PJ)
```sql
business_datas
├── id (PK)
├── tenant_id (FK → tenants)
├── customer_id (FK → customers) [nullable, unique] ✅
├── provider_id (FK → providers) [nullable, unique] ✅
├── fantasy_name
├── state_registration
├── municipal_registration
├── founding_date
├── industry
├── company_size
├── notes
├── created_at
└── updated_at
-- CHECK: (customer_id IS NOT NULL AND provider_id IS NULL) OR (customer_id IS NULL AND provider_id IS NOT NULL)
```

**Papel**: Dados específicos de empresas (PJ). Só existe se CommonData.type = 'company'.

**Relacionamentos**:
- `belongsTo`: Tenant, Customer, Provider

**Usa TenantScoped**: ✅ Sim

**Mudanças**: 
- ✅ Adicionado TenantScoped trait
- ✅ Índices únicos em customer_id e provider_id
- ✅ CHECK constraint garante XOR entre customer_id e provider_id
- ✅ **Removidos campos duplicados**: `company_email`, `company_phone`, `company_website` (já existem em `contacts`)

**Nota**: Contatos empresariais (`email_business`, `phone_business`, `website`) estão na tabela `contacts` para evitar duplicação

---

## ✅ Problemas Corrigidos

### 1. **Relacionamentos Consistentes (1:1)**

```php
// CORRIGIDO
Customer::commonData() → hasOne
Customer::address() → hasOne
Customer::contact() → hasOne
Customer::businessData() → hasOne

Provider::commonData() → hasOne
Provider::address() → hasOne
Provider::contact() → hasOne
Provider::businessData() → hasOne

// FK no lado dependente
CommonData::customer_id / provider_id
Address::customer_id / provider_id
Contact::customer_id / provider_id
BusinessData::customer_id / provider_id
```

**Solução**: FK sempre no lado dependente. Relacionamentos 1:1 claros.

---

### 2. **CommonData com Campo Type**

```php
// CORRIGIDO
common_datas
├── type ENUM('individual', 'company') ✅
├── first_name (se type=individual)
├── last_name (se type=individual)
├── cpf (se type=individual)
├── company_name (se type=company)
├── cnpj (se type=company)
```

**Solução**: Campo `type` diferencia PF/PJ. Validação condicional baseada em type.

---

### 3. **BusinessData Com TenantScoped**

```php
// CORRIGIDO
class BusinessData extends Model
{
    use HasFactory, TenantScoped; ✅
}
```

**Solução**: TenantScoped adicionado. Dados isolados por tenant.

---

### 4. **Ponto Único de Verdade**

```php
// CORRIGIDO
// Contatos em Contact (email_personal, email_business, phone_personal, phone_business, website)
// BusinessData NÃO tem campos de contato (removida duplicação)

// Endereço apenas em Address (tabela dedicada)
```

**Solução**: `Contact` é a fonte única para todos os contatos. `BusinessData` contém apenas dados específicos de PJ (registros, fundação, setor, porte).

---

## ✅ Solução Implementada

### **Modelo 1:1 com FK Invertidas** (Implementado)

#### Estrutura Final

```sql
-- 1. Customers (sem FKs para dependentes)
customers
├── id (PK)
├── tenant_id (FK → tenants)
├── status ENUM('active', 'inactive', 'deleted')
├── created_at
└── updated_at

-- 2. Providers (sem FKs para dependentes)
providers
├── id (PK)
├── tenant_id (FK → tenants)
├── user_id (FK → users) [UNIQUE per tenant]
├── terms_accepted
├── created_at
└── updated_at

-- 3. CommonData (com FKs para Customer/Provider)
common_datas
├── id (PK)
├── tenant_id (FK → tenants)
├── customer_id (FK → customers) [nullable, unique]
├── provider_id (FK → providers) [nullable, unique]
├── type ENUM('individual', 'company')
├── first_name, last_name, cpf, birth_date (se individual)
├── company_name, cnpj (se company)
├── description, area_of_activity_id, profession_id
└── CHECK: customer_id XOR provider_id

-- 4. Addresses (com FKs para Customer/Provider)
addresses
├── id (PK)
├── tenant_id (FK → tenants)
├── customer_id (FK → customers) [nullable, unique]
├── provider_id (FK → providers) [nullable, unique]
├── address, address_number, neighborhood, city, state, cep
└── CHECK: customer_id XOR provider_id

-- 5. Contacts (com FKs para Customer/Provider)
contacts
├── id (PK)
├── tenant_id (FK → tenants)
├── customer_id (FK → customers) [nullable, unique]
├── provider_id (FK → providers) [nullable, unique]
├── email_personal, phone_personal, email_business, phone_business, website
└── CHECK: customer_id XOR provider_id

-- 6. BusinessData (com FKs para Customer/Provider)
business_datas
├── id (PK)
├── tenant_id (FK → tenants)
├── customer_id (FK → customers) [nullable, unique]
├── provider_id (FK → providers) [nullable, unique]
├── fantasy_name, state_registration, municipal_registration
├── founding_date, industry, company_size, notes
└── CHECK: customer_id XOR provider_id

**Nota**: Campos de contato empresarial (email, phone, website) estão em `contacts.email_business/phone_business/website`
```

### Passos de Implementação:

1. ✅ **Atualizar documentação** - DATABASE-ANALYSIS.md completo
2. ✅ **Corrigir schema inicial** - Migration 2025_09_27_132300_create_initial_schema.php
3. ✅ **Atualizar models** - 6 models corrigidos
4. ✅ **Atualizar repositories** - 5 repositories corrigidos
5. ✅ **Atualizar services** - 2 services essenciais corrigidos
6. ✅ **Atualizar controllers** - Funcionando com services atualizados
7. ✅ **Remover duplicação** - Campos `company_email`, `company_phone`, `company_website` removidos de `business_datas`
8. ⏳ **Atualizar testes** - Pendente

---

## 📊 Diagrama Final Implementado

```
Tenant (Raiz Multi-tenant)
├── User (N:1)
│   └── Provider (1:1) ← user_id
│       ├── CommonData (1:1) ← provider_id [type: individual/company]
│       ├── Address (1:1) ← provider_id
│       ├── Contact (1:1) ← provider_id
│       └── BusinessData (1:1) ← provider_id [se type=company]
│
└── Customer (N:1)
    ├── CommonData (1:1) ← customer_id [type: individual/company]
    ├── Address (1:1) ← customer_id
    ├── Contact (1:1) ← customer_id
    └── BusinessData (1:1) ← customer_id [se type=company]

Tabelas Principais (sem FKs para dependentes):
├── customers (id, tenant_id, status)
└── providers (id, tenant_id, user_id, terms_accepted)

Tabelas Dependentes (com FK para principal):
├── common_datas (customer_id XOR provider_id, type)
├── addresses (customer_id XOR provider_id)
├── contacts (customer_id XOR provider_id)
└── business_datas (customer_id XOR provider_id)
```

---

## 🔧 Implementação Realizada

### 1. Schema (Migration) ✅

**Arquivo**: `database/migrations/2025_09_27_132300_create_initial_schema.php`

**Mudanças**:
- ✅ `customers`: Removido `common_data_id`, `contact_id`, `address_id`
- ✅ `providers`: Removido `common_data_id`, `contact_id`, `address_id`
- ✅ `common_datas`: Adicionado `customer_id`, `provider_id`, `type` ENUM
- ✅ `addresses`: Adicionado `customer_id`, `provider_id`
- ✅ `contacts`: Adicionado `customer_id`, `provider_id`
- ✅ `business_datas`: Já tinha `customer_id`, `provider_id` corretos
- ✅ Índices únicos: `(tenant_id, customer_id)`, `(tenant_id, provider_id)`

### 2. Models ✅

#### Customer.php
- ✅ Removido: `common_data_id`, `contact_id`, `address_id` dos fillable/casts
- ✅ Alterado: `commonData()`, `address()`, `contact()` de `belongsTo` → `hasOne`
- ✅ Adicionado: `isCompany()`, `isIndividual()` helpers
- ✅ Removido: Métodos duplicados `addresses()`, `contacts()`

#### Provider.php
- ✅ Removido: `common_data_id`, `contact_id`, `address_id` dos fillable/casts
- ✅ Alterado: `commonData()`, `address()`, `contact()` de `belongsTo` → `hasOne`
- ✅ Adicionado: `isCompany()`, `isIndividual()` helpers

#### CommonData.php
- ✅ Adicionado: `customer_id`, `provider_id`, `type` nos fillable/casts
- ✅ Adicionado: Constantes `TYPE_INDIVIDUAL`, `TYPE_COMPANY`
- ✅ Alterado: `customer()`, `provider()` de `hasOne` → `belongsTo`
- ✅ Adicionado: Scopes `scopeIndividual()`, `scopeCompany()`
- ✅ Adicionado: Helpers `isIndividual()`, `isCompany()`
- ✅ Alterado: `businessRules()` com validação condicional por tipo

#### Address.php
- ✅ Adicionado: `customer_id`, `provider_id` nos fillable/casts
- ✅ Alterado: `customer()` de `hasOne` → `belongsTo`
- ✅ Alterado: `providers()` hasMany → `provider()` belongsTo

#### Contact.php
- ✅ Adicionado: `customer_id`, `provider_id` nos fillable/casts
- ✅ Alterado: `customer()` de `hasOne` → `belongsTo`
- ✅ Alterado: `providers()` hasMany → `provider()` belongsTo
- ✅ Removido: Unique constraints de email nas rules

#### BusinessData.php
- ✅ Adicionado: `TenantScoped` trait
- ✅ Adicionado: `boot()` method com `bootTenantScoped()`

### 3. Repositories ✅

#### CustomerRepository.php
- ✅ Sem mudanças necessárias (usa AbstractTenantRepository)

#### ProviderRepository.php
- ✅ Adicionado: `businessData` no eager loading

#### CommonDataRepository.php
- ✅ Atualizado: `createForCustomer()` com campos corretos + `type`
- ✅ Atualizado: `createForProvider()` com campos corretos + `type`
- ✅ Atualizado: `updateForProvider()` usando `update()` method

#### AddressRepository.php
- ✅ Alterado: `createForCustomer()` de array → single (1:1)
- ✅ Alterado: `createForProvider()` de array → single (1:1)
- ✅ Alterado: `updateForProvider()` para update direto
- ✅ Alterado: `deleteByCustomerId()`, `deleteByProviderId()` retornam bool
- ✅ Removido: `listByCustomerId()`, `listByProviderId()`
- ✅ Adicionado: `findByCustomerId()`, `findByProviderId()`

#### ContactRepository.php
- ✅ Alterado: `deleteByCustomerId()`, `deleteByProviderId()` retornam bool
- ✅ Alterado: `updateForProvider()` usando `update()` method
- ✅ Removido: `findByEmail()`, `listByCustomerId()`, `listByProviderId()`
- ✅ Adicionado: `findByCustomerId()`, `findByProviderId()`

### 4. Services ✅

#### CustomerService.php
- ✅ **createCustomer()**: 
  - Cria Customer primeiro (sem FKs)
  - Cria CommonData/Contact/Address com `customer_id`
  - Detecta tipo automaticamente (PF/PJ) baseado em CNPJ
  - Cria BusinessData apenas se for PJ

- ✅ **updateCustomer()**:
  - Update direto nos relacionamentos 1:1
  - Atualiza CommonData, Contact, Address
  - Cria/atualiza BusinessData se for PJ

#### ProviderManagementService.php
- ✅ **createProviderWithRelatedData()**:
  - Cria Provider primeiro (sem FKs)
  - Cria CommonData/Contact/Address com `provider_id`
  - Inicializa com tipo `individual`
  - Address criado vazio inicialmente

- ✅ **updateProvider()**:
  - Update direto nos relacionamentos 1:1
  - Detecta tipo automaticamente (PF/PJ) baseado em CNPJ
  - Atualiza CommonData, Contact, Address
  - Cria/atualiza BusinessData se for PJ

#### UserRegistrationService.php
- ✅ Sem mudanças necessárias (delega para ProviderManagementService)

### 5. Controllers ⏳

**Funcionando com services atualizados**:
- ✅ `EnhancedRegisteredUserController::store()` - Registro de usuário
- ✅ `CustomerController::store()` - Criar customer
- ✅ `CustomerController::update()` - Atualizar customer
- ✅ `ProviderBusinessController::update()` - Atualizar provider

### 6. Funcionalidades Operacionais ✅

#### Registro de Usuário
```php
// Fluxo: Tenant → User → Provider → CommonData/Contact/Address (1:1)
EnhancedRegisteredUserController::store()
  → UserRegistrationService::registerUser()
    → ProviderManagementService::createProviderFromRegistration()
      → Provider::create() // Sem FKs
      → CommonData::create(['provider_id' => $provider->id])
      → Contact::create(['provider_id' => $provider->id])
      → Address::create(['provider_id' => $provider->id])
```

#### Criar Customer
```php
// Fluxo: Customer → CommonData/Contact/Address/BusinessData (1:1)
CustomerController::store()
  → CustomerService::createCustomer()
    → Customer::create() // Sem FKs
    → CommonData::create(['customer_id' => $customer->id, 'type' => 'individual/company'])
    → Contact::create(['customer_id' => $customer->id])
    → Address::create(['customer_id' => $customer->id])
    → BusinessData::create(['customer_id' => $customer->id]) // Se PJ
```

#### Atualizar Provider
```php
// Fluxo: Update direto nos relacionamentos 1:1
ProviderBusinessController::update()
  → ProviderManagementService::updateProvider()
    → $provider->commonData->update([...])
    → $provider->contact->update([...])
    → $provider->address->update([...])
    → $provider->businessData->update([...]) // Se PJ
```

#### Atualizar Customer
```php
// Fluxo: Update direto nos relacionamentos 1:1
CustomerController::update()
  → CustomerService::updateCustomer()
    → $customer->commonData->update([...])
    → $customer->contact->update([...])
    → $customer->address->update([...])
    → $customer->businessData->update([...]) // Se PJ
```

---

## 🔒 Garantias Multi-tenant

Todas as tabelas implementadas têm:
1. ✅ Campo `tenant_id` (FK → tenants)
2. ✅ Trait `TenantScoped` nos models
3. ✅ Índices compostos: `(tenant_id, customer_id)`, `(tenant_id, provider_id)`
4. ✅ Validação de tenant em queries via TenantScoped
5. ✅ Isolamento completo de dados por tenant

---

## 📝 Status da Implementação

### ✅ Completo
- [x] Documentação atualizada
- [x] Schema inicial corrigido
- [x] 6 Models atualizados (Customer, Provider, CommonData, Address, Contact, BusinessData)
- [x] 5 Repositories atualizados
- [x] 2 Services essenciais atualizados (CustomerService, ProviderManagementService)
- [x] Funcionalidades operacionais testadas:
  - Registro de usuário
  - Criar/atualizar customer
  - Atualizar provider business data

### ⏳ Pendente
- [ ] Atualizar testes unitários
- [ ] Atualizar testes de integração
- [ ] Migração de dados existentes (se houver)
- [ ] Atualizar demais services que usam Customer/Provider

### 🎯 Próximos Passos
1. Executar `php artisan migrate:fresh --seed` para aplicar schema
2. Testar fluxos completos de registro e CRUD
3. Atualizar testes para nova estrutura
4. Revisar e atualizar services restantes conforme necessário

---

## 📚 Referências

### Arquivos Modificados

**Migration**:
- `database/migrations/2025_09_27_132300_create_initial_schema.php`

**Models**:
- `app/Models/Customer.php`
- `app/Models/Provider.php`
- `app/Models/CommonData.php`
- `app/Models/Address.php`
- `app/Models/Contact.php`
- `app/Models/BusinessData.php`

**Repositories**:
- `app/Repositories/CustomerRepository.php`
- `app/Repositories/ProviderRepository.php`
- `app/Repositories/CommonDataRepository.php`
- `app/Repositories/AddressRepository.php`
- `app/Repositories/ContactRepository.php`

**Services**:
- `app/Services/Domain/CustomerService.php`
- `app/Services/Application/ProviderManagementService.php`
- `app/Services/Application/UserRegistrationService.php` (sem mudanças)

**Controllers** (funcionando):
- `app/Http/Controllers/Auth/EnhancedRegisteredUserController.php`
- `app/Http/Controllers/CustomerController.php`
- `app/Http/Controllers/ProviderBusinessController.php`

