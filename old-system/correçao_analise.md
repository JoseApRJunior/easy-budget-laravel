# 📋 PLANO DE CORREÇÕES DOS MODELOS - EXECUÇÃO ESTRUTURADA

## 📋 LOTE 1: RELACIONAMENTOS (3 modelos)

<!--
### **TAREFA 1.1: RolePermission**

**Arquivo:** `app/Models/RolePermission.php`
**Status:** Relacionamentos reversos ausentes

**✅ Ações a executar:**

1. Adicionar relacionamento `role()`:

```php
public function role(): BelongsTo
{
    return $this->belongsTo(Role::class);
}
```

2. Adicionar relacionamento `permission()`:

```php
public function permission(): BelongsTo
{
    return $this->belongsTo(Permission::class);
}
```

3. Adicionar relacionamento `tenant()`:

```php
public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}
```

**✅ Critérios de aceitação:**

-  Relacionamentos compilam sem erro
-  Relacionamentos reversos funcionam corretamente
-  Integridade referencial mantida

---

### **TAREFA 1.2: BudgetStatus**

**Arquivo:** `app/Models/BudgetStatus.php`
**Status:** Relacionamento reverso ausente

**✅ Ações a executar:**

1. Adicionar relacionamento `budgets()`:

```php
public function budgets(): HasMany
{
    return $this->hasMany(Budget::class, 'budget_statuses_id');
}
```

**✅ Critérios de aceitação:**

-  Relacionamento compila sem erro
-  Query `BudgetStatus::first()->budgets` retorna resultados corretos

---

### **TAREFA 1.3: InvoiceStatus**

**Arquivo:** `app/Models/InvoiceStatus.php`
**Status:** Relacionamento reverso ausente

**✅ Ações a executar:**

1. Adicionar relacionamento `invoices()`:

```php
public function invoices(): HasMany
{
    return $this->hasMany(Invoice::class, 'invoice_statuses_id');
}
```

**✅ Critérios de aceitação:**

-  Relacionamento compila sem erro
-  Query `InvoiceStatus::first()->invoices` retorna resultados corretos

--- -->

<!-- ## 📋 LOTE 2: BUSINESSRULES - PARTE 1 (5 modelos)

### **TAREFA 2.1: Invoice**

**Arquivo:** `app/Models/Invoice.php`
**Status:** BusinessRules vazio

**✅ Ações a executar:**

1. Implementar método `businessRules()` completo:

```php
public static function businessRules(): array
{
    return [
        'tenant_id'           => 'required|integer|exists:tenants,id',
        'service_id'          => 'required|integer|exists:services,id',
        'customer_id'         => 'required|integer|exists:customers,id',
        'invoice_statuses_id' => 'required|integer|exists:invoice_statuses,id',
        'code'                => 'required|string|max:50|unique:invoices,code',
        'subtotal'            => 'required|numeric|min:0|max:999999.99',
        'discount'            => 'required|numeric|min:0|max:999999.99',
        'total'               => 'required|numeric|min:0|max:999999.99',
        'due_date'            => 'nullable|date|after:today',
        'payment_method'      => 'nullable|string|max:50',
        'payment_id'          => 'nullable|string|max:255',
        'transaction_amount'  => 'nullable|numeric|min:0|max:999999.99',
        'transaction_date'    => 'nullable|datetime',
        'notes'               => 'nullable|string|max:65535',
    ];
}
```

**✅ Critérios de aceitação:**

-  Todas as regras de validação compilam
-  Validações de existência de chaves estrangeiras funcionam
-  Validações de formato de dados estão corretas

---

### **TAREFA 2.2: Customer**

**Arquivo:** `app/Models/Customer.php`
**Status:** BusinessRules vazio

**✅ Ações a executar:**

1. Implementar método `businessRules()`:

```php
public static function businessRules(): array
{
    return [
        'tenant_id'      => 'required|integer|exists:tenants,id',
        'common_data_id' => 'nullable|integer|exists:common_datas,id',
        'contact_id'     => 'nullable|integer|exists:contacts,id',
        'address_id'     => 'nullable|integer|exists:addresses,id',
        'status'         => 'required|string|in:active,inactive,deleted',
    ];
}
```

---

### **TAREFA 2.3: Provider**

**Arquivo:** `app/Models/Provider.php`
**Status:** BusinessRules vazio

**✅ Ações a executar:**

1. Implementar método `businessRules()`:

```php
public static function businessRules(): array
{
    return [
        'tenant_id'      => 'required|integer|exists:tenants,id',
        'user_id'        => 'required|integer|exists:users,id',
        'common_data_id' => 'nullable|integer|exists:common_datas,id',
        'contact_id'     => 'nullable|integer|exists:contacts,id',
        'address_id'     => 'nullable|integer|exists:addresses,id',
        'terms_accepted' => 'required|boolean',
    ];
}
```

---

### **TAREFA 2.4: Address**

**Arquivo:** `app/Models/Address.php`
**Status:** BusinessRules vazio

**✅ Ações a executar:**

1. Implementar método `businessRules()`:

```php
public static function businessRules(): array
{
    return [
        'tenant_id'      => 'required|integer|exists:tenants,id',
        'address'        => 'required|string|max:255',
        'address_number' => 'nullable|string|max:20',
        'neighborhood'   => 'required|string|max:100',
        'city'           => 'required|string|max:100',
        'state'          => 'required|string|max:2',
        'cep'            => 'required|string|max:9|regex:/^\d{5}-?\d{3}$/',
    ];
}
```

---

### **TAREFA 2.5: CommonData**

**Arquivo:** `app/Models/CommonData.php`
**Status:** BusinessRules vazio

**✅ Ações a executar:**

1. Implementar método `businessRules()`:

```php
public static function businessRules(): array
{
    return [
        'tenant_id'           => 'required|integer|exists:tenants,id',
        'first_name'          => 'required|string|max:100',
        'last_name'           => 'required|string|max:100',
        'birth_date'          => 'nullable|date|before:today',
        'cnpj'                => 'nullable|string|size:14|unique:common_datas,cnpj',
        'cpf'                 => 'nullable|string|size:11|unique:common_datas,cpf',
        'company_name'        => 'nullable|string|max:255',
        'description'         => 'nullable|string|max:65535',
        'area_of_activity_id' => 'nullable|integer|exists:area_of_activities,id',
        'profession_id'       => 'nullable|integer|exists:professions,id',
    ];
}
```

---

## 📋 LOTE 3: BUSINESSRULES - PARTE 2 (5 modelos)

### **TAREFA 3.1: Contact**

**Arquivo:** `app/Models/Contact.php`
**Status:** BusinessRules vazio

**✅ Ações a executar:**

1. Implementar método `businessRules()`:

```php
public static function businessRules(): array
{
    return [
        'tenant_id'      => 'required|integer|exists:tenants,id',
        'email'          => 'required|email|max:255|unique:contacts,email',
        'phone'          => 'nullable|string|max:20',
        'email_business' => 'nullable|email|max:255|unique:contacts,email_business',
        'phone_business' => 'nullable|string|max:20',
        'website'        => 'nullable|url|max:255',
    ];
}
```

---

### **TAREFA 3.2: Notification**

**Arquivo:** `app/Models/Notification.php`
**Status:** BusinessRules vazio

**✅ Ações a executar:**

1. Implementar método `businessRules()`:

```php
public static function businessRules(): array
{
    return [
        'tenant_id' => 'required|integer|exists:tenants,id',
        'type'      => 'required|string|max:50',
        'email'     => 'required|email|max:255',
        'message'   => 'required|string|max:65535',
        'subject'   => 'required|string|max:255',
        'sent_at'   => 'nullable|datetime',
    ];
}
```

---

### **TAREFA 3.3: Activity**

**Arquivo:** `app/Models/Activity.php`
**Status:** BusinessRules vazio

**✅ Ações a executar:**

1. Implementar método `businessRules()`:

```php
public static function businessRules(): array
{
    return [
        'tenant_id'   => 'required|integer|exists:tenants,id',
        'user_id'     => 'required|integer|exists:users,id',
        'action_type' => 'required|string|max:50',
        'entity_type' => 'required|string|max:50',
        'entity_id'   => 'required|integer',
        'description' => 'required|string|max:65535',
        'metadata'    => 'nullable|string|max:65535',
    ];
}
```

---

### **TAREFA 3.4: BudgetStatus**

**Arquivo:** `app/Models/BudgetStatus.php`
**Status:** BusinessRules vazio

**✅ Ações a executar:**

1. Implementar método `businessRules()`:

```php
public static function businessRules(): array
{
    return [
        'slug'        => 'required|string|max:50|unique:budget_statuses,slug',
        'name'        => 'required|string|max:100|unique:budget_statuses,name',
        'description' => 'nullable|string|max:500',
        'color'       => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i',
        'icon'        => 'nullable|string|max:50',
        'order_index' => 'nullable|integer|min:0',
        'is_active'   => 'required|boolean',
    ];
}
```

---

### **TAREFA 3.5: InvoiceStatus**

**Arquivo:** `app/Models/InvoiceStatus.php`
**Status:** BusinessRules vazio

**✅ Ações a executar:**

1. Implementar método `businessRules()`:

```php
public static function businessRules(): array
{
    return [
        'name'        => 'required|string|max:100|unique:invoice_statuses,name',
        'slug'        => 'required|string|max:50|unique:invoice_statuses,slug',
        'description' => 'nullable|string|max:500',
        'color'       => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i',
        'icon'        => 'nullable|string|max:50',
        'order_index' => 'nullable|integer|min:0',
        'is_active'   => 'required|boolean',
    ];
}
```

--- -->
<!--
## 📋 LOTE 4: VALIDAÇÃO FINAL (2 modelos restantes)

### **TAREFA 4.1: PaymentMercadoPagoInvoice**

**Arquivo:** `app/Models/PaymentMercadoPagoInvoice.php`
**Status:** BusinessRules vazio

**✅ Ações a executar:**

1. Implementar método `businessRules()`:

```php
public static function businessRules(): array
{
    return [
        'payment_id'         => 'required|string|max:255',
        'tenant_id'          => 'required|integer|exists:tenants,id',
        'invoice_id'         => 'required|integer|exists:invoices,id',
        'status'             => 'required|string|in:pending,approved,rejected,cancelled,refunded',
        'payment_method'     => 'required|string|in:credit_card,debit_card,bank_transfer,ticket,pix',
        'transaction_amount' => 'required|numeric|min:0|max:999999.99',
        'transaction_date'   => 'nullable|datetime',
    ];
}
```

---

### **TAREFA 4.2: AlertSetting**

**Arquivo:** `app/Models/AlertSetting.php`
**Status:** BusinessRules vazio

**✅ Ações a executar:**

1. Implementar método `businessRules()`:

```php
public static function businessRules(): array
{
    return [
        'tenant_id' => 'required|integer|exists:tenants,id',
        'settings'  => 'required|array',
    ];
}
``` -->

---

## 🎯 PRÓXIMOS PASSOS SUGERIDOS

### **Após completar todas as correções:**

<!-- 1. **Executar análise de sintaxe** em todos os modelos -->

3. **Validar BusinessRules** com dados de teste
4. **Verificar integridade** do banco de dados
5. **Documentar mudanças** implementadas

### **Ferramentas recomendadas:**

-  **PHPStan** para análise estática
-  **Laravel Pint** para formatação de código
-  **Testes unitários** para validação de relacionamentos
-  **Análise de logs** para identificar problemas

---

## 📊 CONTROLE DE PROGRESSO

**Status Inicial:** 8/20 modelos (40% conformidade)
**Meta Final:** 20/20 modelos (100% conformidade)

**Lote 1 (Relacionamentos):** 0/3 ✅
**Lote 2 (BusinessRules Pt1):** 0/5 ✅
**Lote 3 (BusinessRules Pt2):** 0/5 ✅
**Lote 4 (Validação Final):** 0/2 ✅

**Total de Correções:** 0/15 implementadas
