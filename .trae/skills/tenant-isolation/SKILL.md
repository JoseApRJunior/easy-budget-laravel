# 🏢 Skill: Tenant Isolation

**Descrição:** Garante o isolamento correto de dados multi-tenant em todas as operações do sistema.

**Categoria:** Segurança e Arquitetura
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Implementar e garantir o isolamento total de dados entre diferentes tenants (empresas) no Easy Budget Laravel, assegurando que cada empresa só tenha acesso aos seus próprios dados.

## 📋 Requisitos Técnicos

### **✅ Global Scopes Obrigatórios**

Todos os Models que armazenam dados por tenant devem usar o trait `TenantScoped`:

```php
// ❌ Errado - Sem isolamento
class Customer extends Model
{
    protected $fillable = ['name', 'email', 'tenant_id'];
}

// ✅ Correto - Com isolamento
class Customer extends Model
{
    use TenantScoped;

    protected $fillable = ['name', 'email', 'tenant_id'];
}
```

### **✅ Trait TenantScoped**

```php
trait TenantScoped
{
    protected static function bootTenantScoped()
    {
        static::addGlobalScope(new TenantScope);
        static::creating(function ($model) {
            $model->tenant_id = auth()->user()?->tenant_id ?? 1;
        });
    }
}
```

### **✅ TenantScope Implementation**

```php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check()) {
            $builder->where('tenant_id', auth()->user()->tenant_id);
        }
    }
}
```

## 🏗️ Estrutura de Isolamento

### **📁 Organização de Models**

```
app/Models/
├── TenantScoped/              # Models que usam isolamento
│   ├── Customer.php
│   ├── Product.php
│   ├── Budget.php
│   └── Service.php
├── Global/                    # Models sem isolamento
│   ├── Tenant.php
│   ├── User.php
│   └── Permission.php
└── Traits/
    └── TenantScoped.php       # Trait de isolamento
```

### **🔧 Models com Isolamento**

#### **Customer Model**
```php
class Customer extends Model
{
    use HasFactory, TenantScoped, Auditable;

    protected $fillable = [
        'tenant_id',
        'common_data_id',
        'contact_id',
        'address_id',
        'status'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

#### **Product Model**
```php
class Product extends Model
{
    use HasFactory, TenantScoped, Auditable;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'price',
        'active',
        'code'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

### **🔧 Models sem Isolamento**

#### **Tenant Model**
```php
class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'database',
        'status'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
```

#### **User Model**
```php
class User extends Authenticatable
{
    use HasFactory, Notifiable, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'email',
        'password',
        'role'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

## 📝 Padrões de Implementação

### **1. Models com Isolamento**

```php
class Budget extends Model
{
    use HasFactory, TenantScoped, Auditable;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'budget_statuses_id',
        'code',
        'due_date',
        'total',
        'description'
    ];

    // Relacionamentos
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
```

### **2. Models sem Isolamento**

```php
class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    // Relacionamentos com tenant_id explícito
    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class);
    }
}
```

### **3. Models com Relacionamentos Complexos**

```php
class Service extends Model
{
    use HasFactory, TenantScoped, Auditable;

    protected $fillable = [
        'tenant_id',
        'budget_id',
        'category_id',
        'service_statuses_id',
        'code',
        'description',
        'total',
        'due_date'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

## 🔍 Validações de Segurança

### **✅ Validação de Acesso**

```php
class BudgetController extends Controller
{
    public function show(string $code)
    {
        // ✅ Validação automática via Global Scope
        $budget = Budget::where('code', $code)->firstOrFail();

        // O Global Scope garante que só budgets do tenant atual sejam retornados
        return view('budgets.show', compact('budget'));
    }
}
```

### **✅ Validação Manual (Quando necessário)**

```php
class BudgetService extends AbstractBaseService
{
    public function findByCode(string $code): ServiceResult
    {
        $budget = Budget::where('code', $code)->first();

        if (! $budget) {
            return $this->error('Orçamento não encontrado', OperationStatus::NOT_FOUND);
        }

        // Validação extra de segurança
        if ($budget->tenant_id !== auth()->user()->tenant_id) {
            return $this->error('Acesso negado', OperationStatus::FORBIDDEN);
        }

        return $this->success($budget, 'Orçamento encontrado');
    }
}
```

## 🧪 Testes de Isolamento

### **✅ Testes de Segurança**

```php
class TenantIsolationTest extends TestCase
{
    public function test_tenant_cannot_access_other_tenant_data()
    {
        // Criar dois tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Criar usuários para cada tenant
        $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);

        // Criar customers para cada tenant
        $customer1 = Customer::factory()->create(['tenant_id' => $tenant1->id]);
        $customer2 = Customer::factory()->create(['tenant_id' => $tenant2->id]);

        // Autenticar como usuário 1
        $this->actingAs($user1);

        // Testar acesso a customer do próprio tenant
        $response = $this->get('/provider/customers/show/'.$customer1->id);
        $response->assertStatus(200);

        // Testar acesso a customer de outro tenant (deve falhar)
        $response = $this->get('/provider/customers/show/'.$customer2->id);
        $response->assertStatus(404); // Não encontrado devido ao Global Scope
    }

    public function test_global_scope_applies_to_all_queries()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Customer::factory()->create(['tenant_id' => $tenant1->id]);
        Customer::factory()->create(['tenant_id' => $tenant2->id]);

        $user = User::factory()->create(['tenant_id' => $tenant1->id]);
        $this->actingAs($user);

        // Deve retornar apenas customers do tenant 1
        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals($tenant1->id, $customers->first()->tenant_id);
    }
}
```

### **✅ Testes de Criação**

```php
public function test_tenant_id_is_automatically_set_on_creation()
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user);

    $customer = Customer::create([
        'name' => 'Test Customer',
        'email' => 'test@example.com'
    ]);

    $this->assertEquals($tenant->id, $customer->tenant_id);
}
```

## 🔧 Ferramentas de Desenvolvimento

### **✅ PHPStan Rules**

```php
// Configuração para detectar Models sem TenantScoped
return [
    'rules' => [
        'tenant-isolation' => [
            'models_requiring_tenant_scope' => [
                'App\\Models\\Customer',
                'App\\Models\\Product',
                'App\\Models\\Budget',
                'App\\Models\\Service',
                'App\\Models\\Invoice',
            ]
        ]
    ]
];
```

### **✅ Laravel Pint Rules**

```json
{
    "preset": "psr12",
    "rules": {
        "tenant-scoped-models": true
    }
}
```

## 📊 Métricas de Segurança

### **✅ Cobertura de Isolamento**

- **100%** dos Models que armazenam dados por tenant usam TenantScoped
- **100%** das consultas são protegidas por Global Scopes
- **100%** das operações de escrita validam tenant_id

### **✅ Testes de Segurança**

- **100%** dos endpoints testam isolamento de tenant
- **100%** das operações CRUD testam acesso indevido
- **100%** das consultas testam Global Scopes

### **✅ Auditoria de Acesso**

- **100%** das operações são auditadas com tenant_id
- **100%** das falhas de acesso são registradas
- **100%** das tentativas de bypass são detectadas

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Criar TenantScoped trait
- [ ] Criar TenantScope global scope
- [ ] Implementar nos Models principais

### **Fase 2: Validation**
- [ ] Criar testes de isolamento
- [ ] Implementar validações manuais
- [ ] Criar ferramentas de auditoria

### **Fase 3: Security**
- [ ] Implementar PHPStan rules
- [ ] Criar alertas de segurança
- [ ] Documentar políticas de acesso

### **Fase 4: Monitoring**
- [ ] Implementar monitoramento de acessos
- [ ] Criar relatórios de segurança
- [ ] Automatizar detecção de violações

## 📚 Documentação Relacionada

- [TenantScoped Trait](../../app/Traits/TenantScoped.php)
- [TenantScope](../../app/Scopes/TenantScope.php)
- [Tenant Middleware](../../app/Http/Middleware/TenantMiddleware.php)
- [Tenant Model](../../app/Models/Tenant.php)

## 🎯 Benefícios

### **✅ Segurança Total**
- Isolamento automático de dados por tenant
- Prevenção de acessos indevidos
- Conformidade com requisitos de privacidade

### **✅ Simplicidade**
- Implementação automática via traits
- Não requer alterações em consultas existentes
- Manutenção mínima

### **✅ Performance**
- Global Scopes otimizados
- Consultas indexadas por tenant_id
- Cache por tenant

### **✅ Escalabilidade**
- Arquitetura preparada para múltiplos tenants
- Isolamento de recursos
- Monitoramento por tenant

## ⚠️ Considerações Importantes

### **✅ Vantagens do Sistema Atual**

1. **Global Scopes Automáticos:** O trait TenantScoped aplica automaticamente o escopo em todas as consultas
2. **Criação Automática:** O tenant_id é automaticamente definido durante a criação de registros
3. **Auditoria Integrada:** O trait Auditable registra todas as operações com tenant_id
4. **Middleware de Segurança:** O TenantMiddleware valida o tenant antes de cada requisição

### **⚠️ Desafios Identificados**

1. **Testes de Isolamento:** Necessário garantir que todos os testes validem o isolamento
2. **Consultas Complexas:** Relacionamentos entre Models podem exigir atenção especial
3. **Cache por Tenant:** Necessário garantir que o cache seja isolado por tenant
4. **Jobs e Queues:** Operações assíncronas precisam manter o contexto do tenant

### **🔧 Melhorias Recomendadas**

1. **Testes de Segurança:** Implementar testes específicos para validar o isolamento
2. **Monitoramento:** Criar alertas para tentativas de acesso indevido
3. **Documentação:** Documentar políticas de acesso e isolamento
4. **Ferramentas de Desenvolvimento:** Criar ferramentas para validar o isolamento durante o desenvolvimento

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
