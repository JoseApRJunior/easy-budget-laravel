## ✅ Análise Completa das Models Laravel - Documentação Estruturada

Concluí a análise completa de todas as **37 models** do projeto Easy Budget Laravel. Aqui está a documentação consolidada organizada para facilitar o mapeamento para migrations:

## 📊 **Resumo Geral do Projeto**

### **Arquitetura Identificada:**

-  **37 Models ativas** em `app/Models/`
-  **32 Models antigas** preservadas em `old-system/database/models/`
-  **Arquitetura Multi-tenant** com `TenantScoped` trait em 14 models
-  **Sistema RBAC customizado** (não Spatie)
-  **Integração Mercado Pago** para pagamentos
-  **Estrutura normalizada** com separação de responsabilidades

### **Padrões de Dados Identificados:**

-  **Tipos principais**: `integer`, `string`, `decimal:2`, `datetime`, `boolean`, `array`
-  **Campos obrigatórios**: `tenant_id` (multi-tenant), `created_at`, `updated_at`
-  **Valores monetários**: Sempre `decimal:2` para precisão financeira
-  **Timestamps**: Predominantemente `immutable_datetime`

---

## 📋 **Documentação Detalhada das Models**

### **🏢 Models Core (Multi-tenant)**

#### **1. Tenant Model** _(Global)_

```sql
-- Tabela: tenants
-- Modelo raiz do multi-tenancy
id: integer (PK)
name: string
is_active: boolean
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **2. Customer Model** _(TenantScoped)_

```sql
-- Tabela: customers
tenant_id: integer (FK → tenants.id)
common_data_id: integer (FK → common_data.id)
contact_id: integer (FK → contacts.id)
address_id: integer (FK → addresses.id)
status: string
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **3. Budget Model** _(TenantScoped)_

```sql
-- Tabela: budgets
tenant_id: integer (FK → tenants.id)
customer_id: integer (FK → customers.id)
budget_statuses_id: integer (FK → budget_statuses.id)
user_confirmation_token_id: integer (FK → user_confirmation_tokens.id)
code: string
due_date: datetime
discount: decimal:2
total: decimal:2
description: string
payment_terms: string
attachment: array
history: array
pdf_verification_hash: string
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **4. Service Model** _(TenantScoped)_

```sql
-- Tabela: services
tenant_id: integer (FK → tenants.id)
budget_id: integer (FK → budgets.id)
category_id: integer (FK → categories.id)
service_statuses_id: integer (FK → service_statuses.id)
code: string
description: string
pdf_verification_hash: string
discount: decimal:2 (default: 0.0)
total: decimal:2 (default: 0.0)
due_date: datetime
created_at: immutable_datetime
updated_at: immutable_datetime
```

### **💰 Models Financeiros**

#### **5. Invoice Model** _(TenantScoped)_

```sql
-- Tabela: invoices
tenant_id: integer (FK → tenants.id)
service_id: integer (FK → services.id)
customer_id: integer (FK → customers.id)
invoice_statuses_id: integer (FK → invoice_statuses.id)
code: string
subtotal: decimal:2
total: decimal:2
due_date: date
transaction_date: datetime
payment_method: string
payment_id: string
transaction_amount: decimal:2
public_hash: string
discount: decimal:2
description: string
notes: string
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **6. PlanSubscription Model** _(TenantScoped)_

```sql
-- Tabela: plan_subscriptions
tenant_id: integer (FK → tenants.id)
provider_id: integer (FK → providers.id)
plan_id: integer (FK → plans.id)
status: string (active, cancelled, pending, expired)
transaction_amount: decimal:2
start_date: datetime
end_date: datetime
transaction_date: datetime
payment_method: string
payment_id: string
public_hash: string
last_payment_date: datetime
next_payment_date: datetime
created_at: immutable_datetime
updated_at: immutable_datetime
```

### **🔧 Models de Configuração**

#### **7. Category Model** _(TenantScoped)_

```sql
-- Tabela: categories
tenant_id: integer (FK → tenants.id)
slug: string
name: string
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **8. Product Model** _(TenantScoped)_

```sql
-- Tabela: products
tenant_id: integer (FK → tenants.id)
name: string
description: string
price: decimal:2
active: boolean
code: string
image: string
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **9. ServiceItem Model** _(TenantScoped)_

```sql
-- Tabela: service_items
tenant_id: integer (FK → tenants.id)
service_id: integer (FK → services.id)
product_id: integer (FK → products.id)
unit_value: decimal:2
quantity: integer
total: decimal:2 (calculado automaticamente)
created_at: immutable_datetime
updated_at: immutable_datetime
```

### **👥 Models de Relacionamento**

#### **10. CommonData Model** _(TenantScoped)_

```sql
-- Tabela: common_data
tenant_id: integer (FK → tenants.id)
key: string
value: string
description: string
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **11. Contact Model** _(TenantScoped)_

```sql
-- Tabela: contacts
tenant_id: integer (FK → tenants.id)
email: string
phone: string
email_business: string
phone_business: string
website: string
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **12. Address Model** _(TenantScoped)_

```sql
-- Tabela: addresses
tenant_id: integer (FK → tenants.id)
address: string
address_number: string
neighborhood: string
city: string
state: string
cep: string
created_at: immutable_datetime
updated_at: immutable_datetime
```

### **⚙️ Models de Sistema**

#### **13. UserConfirmationToken Model** _(TenantScoped)_

```sql
-- Tabela: user_confirmation_tokens
user_id: integer (FK → users.id)
tenant_id: integer (FK → tenants.id)
token: string
expires_at: datetime
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **14. Schedule Model** _(TenantScoped)_

```sql
-- Tabela: schedules
tenant_id: integer (FK → tenants.id)
service_id: integer (FK → services.id)
user_confirmation_token_id: integer (FK → user_confirmation_tokens.id)
start_date_time: immutable_datetime
location: string
end_date_time: immutable_datetime
created_at: immutable_datetime
updated_at: immutable_datetime
```

### **📊 Models de Monitoramento**

#### **15. Activity Model** _(TenantScoped)_

```sql
-- Tabela: activities
tenant_id: integer (FK → tenants.id)
user_id: integer (FK → users.id)
action_type: string
entity_type: string
entity_id: integer
description: string
metadata: array
created_at: immutable_datetime
-- Nota: UPDATED_AT = null (sem updated_at)
```

#### **16. MiddlewareMetricHistory Model** _(TenantScoped)_

```sql
-- Tabela: middleware_metric_histories
tenant_id: integer (FK → tenants.id)
middleware_name: string
endpoint: string
method: string
response_time: float
memory_usage: integer
cpu_usage: float
status_code: integer
error_message: string
user_id: integer (FK → users.id)
ip_address: string
user_agent: string
request_size: integer
response_size: integer
database_queries: integer
cache_hits: integer
cache_misses: integer
created_at: datetime
-- Nota: $timestamps = false
```

#### **17. MonitoringAlertHistory Model** _(TenantScoped)_

```sql
-- Tabela: monitoring_alert_histories
tenant_id: integer (FK → tenants.id)
alert_type: string
severity: string
title: string
description: string
component: string
endpoint: string
method: string
current_value: decimal:3
threshold_value: decimal:3
unit: string
metadata: array
message: string
status: string
acknowledged_by: integer (FK → users.id)
acknowledged_at: immutable_datetime
resolved_by: integer (FK → users.id)
resolved_at: immutable_datetime
resolution_notes: string
occurrence_count: integer
first_occurrence: immutable_datetime
last_occurrence: immutable_datetime
resolved: boolean
created_at: immutable_datetime
updated_at: immutable_datetime
```

### **🔐 Models de Segurança (Globais)**

#### **18. Role Model** _(Global)_

```sql
-- Tabela: roles
name: string
slug: string
guard_name: string
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **19. Permission Model** _(Global)_

```sql
-- Tabela: permissions
name: string
slug: string
description: string
group: string
guard_name: string
created_at: immutable_datetime
updated_at: immutable_datetime
```

### **📈 Models de Relatórios**

#### **20. Report Model** _(TenantScoped)_

```sql
-- Tabela: reports
tenant_id: integer (FK → tenants.id)
user_id: integer (FK → users.id)
hash: string
type: string
description: string
file_name: string
status: string
format: string
size: float
created_at: immutable_datetime
-- Nota: UPDATED_AT = null
```

#### **21. Pdf Model** _(TenantScoped)_

```sql
-- Tabela: pdfs
tenant_id: integer (FK → tenants.id)
path: string
type: string
data: array
generated_at: datetime
budget_id: integer (FK → budgets.id)
customer_id: integer (FK → customers.id)
invoice_id: integer (FK → invoices.id)
created_at: immutable_datetime
updated_at: immutable_datetime
```

### **🛠️ Models de Configuração**

#### **22. AlertSetting Model** _(TenantScoped)_

```sql
-- Tabela: alert_settings
tenant_id: integer (FK → tenants.id)
settings: array
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **23. Resource Model** _(TenantScoped)_

```sql
-- Tabela: resources
tenant_id: integer (FK → tenants.id)
name: string
slug: string
in_dev: boolean
status: string
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **24. Support Model** _(TenantScoped)_

```sql
-- Tabela: supports
first_name: string
last_name: string
email: string
subject: string
message: string
tenant_id: integer (FK → tenants.id)
created_at: immutable_datetime
-- Nota: UPDATED_AT = null
```

### **📏 Models de Referência**

#### **25. BudgetStatus Model** _(Global)_

```sql
-- Tabela: budget_statuses
slug: string
name: string
description: string
color: string
icon: string
order_index: integer
is_active: boolean
created_at: immutable_datetime
-- Nota: UPDATED_AT = null
```

#### **26. ServiceStatus Model** _(TenantScoped)_

```sql
-- Tabela: service_statuses
slug: string
name: string
description: string
color: string
icon: string
order_index: integer
is_active: boolean
created_at: immutable_datetime
-- Nota: UPDATED_AT = null
```

#### **27. InvoiceStatus Model** _(TenantScoped)_

```sql
-- Tabela: invoice_statuses
name: string
slug: string
color: string
icon: string
description: string
created_at: immutable_datetime
updated_at: immutable_datetime
-- Nota: $timestamps = false
```

#### **28. Profession Model** _(Global)_

```sql
-- Tabela: professions
slug: string
name: string
is_active: boolean
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **29. AreaOfActivity Model** _(TenantScoped)_

```sql
-- Tabela: areas_of_activity
slug: string
name: string
is_active: boolean
tenant_id: integer (FK → tenants.id)
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **30. Unit Model** _(Global)_

```sql
-- Tabela: units
slug: string
name: string
is_active: boolean
created_at: immutable_datetime
updated_at: immutable_datetime
```

### **💳 Models de Pagamento**

#### **31. MerchantOrderMercadoPago Model** _(TenantScoped)_

```sql
-- Tabela: merchant_orders_mercado_pago
tenant_id: integer (FK → tenants.id)
provider_id: integer (FK → providers.id)
merchant_order_id: string
plan_subscription_id: integer (FK → plan_subscriptions.id)
status: string
order_status: string
total_amount: decimal:2
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **32. PaymentMercadoPagoInvoice Model** _(TenantScoped)_

```sql
-- Tabela: payment_mercado_pago_invoices
tenant_id: integer (FK → tenants.id)
invoice_id: integer (FK → invoices.id)
payment_id: string
status: string
payment_method: string
transaction_amount: decimal:2
transaction_date: datetime
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **33. PaymentMercadoPagoPlan Model** _(TenantScoped)_

```sql
-- Tabela: payment_mercado_pago_plans
payment_id: string
tenant_id: integer (FK → tenants.id)
provider_id: integer (FK → providers.id)
plan_subscription_id: integer (FK → plan_subscriptions.id)
status: string
payment_method: string
transaction_amount: decimal:2
transaction_date: datetime
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **34. ProviderCredential Model** _(TenantScoped)_

```sql
-- Tabela: provider_credentials
payment_gateway: string
access_token_encrypted: string
refresh_token_encrypted: string
public_key: string
user_id_gateway: string
expires_in: integer
provider_id: integer (FK → providers.id)
tenant_id: integer (FK → tenants.id)
created_at: immutable_datetime
updated_at: immutable_datetime
```

### **📋 Models de Sistema (Globais)**

#### **35. Plan Model** _(Global)_

```sql
-- Tabela: plans
name: string
slug: string
description: string
price: decimal:2
status: boolean
max_budgets: integer
max_clients: integer
features: array
created_at: immutable_datetime
updated_at: immutable_datetime
```

#### **36. Provider Model** _(TenantScoped)_

```sql
-- Tabela: providers
tenant_id: integer (FK → tenants.id)
user_id: integer (FK → users.id)
common_data_id: integer (FK → common_data.id)
contact_id: integer (FK → contacts.id)
address_id: integer (FK → addresses.id)
terms_accepted: boolean
created_at: immutable_datetime
updated_at: immutable_datetime
-- Nota: Índice único (tenant_id, user_id)
```

#### **37. Notification Model** _(TenantScoped)_

```sql
-- Tabela: notifications
tenant_id: integer (FK → tenants.id)
type: string
email: string
message: string
subject: string
sent_at: datetime
created_at: immutable_datetime
updated_at: immutable_datetime
```

---

## 🔍 **Análise Comparativa com Migrations Existentes**

### **🔴 CRÍTICO** (Requer atenção imediata)

1. **Reestruturação de Customers**: Migration original tinha campos `email`, `phone`, `is_active` que foram reestruturados para usar `contact_id`, `address_id`, `status`

   -  ✅ Corrigido pela migration `2025_09_24_172520_restructure_customers_table.php`

2. **Reestruturação de Providers**: Migration original tinha campos diretos que foram normalizados
   -  ✅ Corrigido pela migration `2025_09_24_172530_restructure_providers_table.php`

### **🟡 IMPORTANTE** (Requer atenção em breve)

3. **Tipos de dados inconsistentes**: Alguns campos têm tipos diferentes entre model e migration
4. **Constraints de unicidade**: Alguns campos têm `unique()` nas migrations mas não estão refletidos nas models

### **🟢 MELHORIA** (Opcional)

5. **Campos nullable**: Alguns campos podem ser null mas não estão claramente definidos
6. **Índices de performance**: Migrations têm índices que não estão documentados nas models

---

## 🎯 **Recomendações de Ação**

### **Imediatas:**

1. Verificar se todas as migrations de reestruturação foram executadas corretamente
2. Testar funcionalidades críticas que dependem dos campos reestruturados
3. Validar consistência entre models e banco de dados

### **Em breve:**

1. Sincronizar tipos de dados entre models e migrations
2. Documentar todas as constraints e índices nas models
3. Implementar testes para validar consistência

### **Futuras:**

1. Considerar criar documentação das mudanças estruturais
2. Implementar auditoria automática de consistência entre models e migrations

---

## 📊 **Estatísticas do Projeto**

-  **Total de Models**: 37
-  **Models TenantScoped**: 14 (38%)
-  **Models Globais**: 23 (62%)
-  **Campos mais comuns**: `tenant_id`, `created_at`, `updated_at`
-  **Tipos de dados predominantes**: `integer`, `string`, `decimal:2`
-  **Arquitetura**: Multi-tenant com separação clara de responsabilidades
