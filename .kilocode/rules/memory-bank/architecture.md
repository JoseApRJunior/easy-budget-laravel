# Architecture - Easy Budget Laravel

## 🏗️ Arquitetura Geral do Sistema

### **📐 Padrões Arquiteturais Utilizados**

#### **🏛️ Arquitetura MVC com Service Layer**

```
Controllers → Services → Repositories → Models → Database
     ↓           ↓          ↓         ↓        ↓
  HTTP     Business    Data       ORM     Relations
  Layer    Logic      Access     Layer   & Migrations
```

#### **🏢 Multi-tenant Architecture**

```
Sistema Global
├── 🌐 Load Balancer (Nginx)
├── 🔐 Authentication Middleware
├── 🏢 Tenant Resolution Service
├── 💾 Database Router
└── 📊 Monitoring & Logging
```

### **🗂️ Estrutura de Diretórios**

#### **📁 App Structure**

```
app/
├── Console/
│   └── Commands/           # Comandos Artisan personalizados
├── Events/                 # Eventos do sistema
├── Http/
│   ├── Controllers/        # Controllers HTTP
│   │   ├── Auth/          # Controllers de autenticação
│   │   ├── Dashboard/     # Dashboard administrativo
│   │   └── Api/           # API controllers
│   ├── Middleware/        # Middlewares customizados
│   └── Requests/          # Form requests
├── Jobs/                  # Jobs para processamento assíncrono
├── Listeners/             # Event listeners
├── Models/                # Eloquent models
├── Providers/             # Service providers
├── Repositories/          # Repository pattern implementation
├── Services/              # Business logic services
├── Traits/                # Traits reutilizáveis
└── Utils/                 # Utilitários e helpers
```

### **🔧 Componentes Principais**

#### **🏢 Tenant Management**

```php
// app/Traits/TenantScoped.php
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

#### **🔐 Authentication & Authorization**

```php
// app/Http/Middleware/TenantMiddleware.php
class TenantMiddleware
{
    public function handle($request, Closure $next)
    {
        $tenant = Tenant::where('domain', $request->getHost())->first();
        config(['tenant.id' => $tenant->id]);
        return $next($request);
    }
}
```

#### **📊 Service Layer Pattern**

```php
// app/Services/FinancialSummary.php
class FinancialSummary
{
    public function getMonthlyRevenue(int $year, int $month): Collection
    {
        return Budget::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('tenant_id', tenant('id'))
            ->sum('total_value');
    }
}
```

### **💾 Modelo de Dados**

#### **🏗️ Relacionamentos Multi-tenant**

```php
// app/Models/User.php
class User extends Authenticatable
{
    use HasFactory, Notifiable, TenantScoped;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'password', 'role'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

#### **🏢 Tenant Model**

```php
// app/Models/Tenant.php
class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'domain', 'database', 'status'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
```

### **🔗 Fluxos de Dados Críticos**

#### **🔐 Fluxo de Autenticação**

```
1. User submits login form
   ↓
2. AuthenticatedSessionController::store()
   ↓
3. Validates credentials with User model
   ↓
4. Creates custom session data
   ↓
5. Redirects to provider.index route
   ↓
6. Loads dashboard with user context
```

#### **💰 Fluxo de Criação de Orçamento**

```
1. User fills budget form
   ↓
2. BudgetController::store()
   ↓
3. Validates with BudgetRequest
   ↓
4. Uses BudgetService for business logic
   ↓
5. Saves via BudgetRepository
   ↓
6. Triggers BudgetCreated event
   ↓
7. Updates financial cache
   ↓
8. Redirects with success message
```

#### **📊 Fluxo de Geração de Relatórios**

```
1. User requests report
   ↓
2. ReportController::generate()
   ↓
3. Uses ReportService for data aggregation
   ↓
4. Queries optimized with eager loading
   ↓
5. Caches results with Redis
   ↓
6. Returns PDF/Excel view
```

### **⚡ Estratégias de Performance**

#### **🚀 Cache Strategy**

```php
// Multi-level caching
$cacheKey = "tenant:{$tenantId}:report:{$type}:" . md5($filters);

// Application level (Redis)
Cache::remember($cacheKey, 3600, function() use ($filters) {
    return $this->reportService->generate($filters);
});

// Database query optimization
$query = Budget::with(['customer', 'items'])
    ->select(['id', 'customer_id', 'total_value', 'created_at'])
    ->where('tenant_id', tenant('id'))
    ->whereBetween('created_at', [$startDate, $endDate])
    ->orderBy('created_at', 'desc');
```

#### **📊 Índices Estratégicos**

```sql
-- Índices compostos para performance (conforme migration)
CREATE INDEX idx_budgets_tenant_status_date ON budgets (tenant_id, status, created_at);
CREATE INDEX idx_budgets_customer_tenant ON budgets (customer_id, tenant_id);
CREATE INDEX idx_customers_tenant_type_name ON customers (tenant_id, type, name);
CREATE INDEX idx_audit_logs_tenant_action_date ON audit_logs (tenant_id, action, created_at);
CREATE INDEX idx_audit_logs_user_tenant ON audit_logs (user_id, tenant_id);
CREATE INDEX idx_audit_logs_severity_category ON audit_logs (severity, category);

-- Índices parciais para queries específicas
CREATE INDEX idx_budgets_tenant_active ON budgets (tenant_id, status) WHERE status = 'active';
```

### **🔒 Segurança e Auditoria**

#### **🛡️ Sistema de Auditoria**

```php
// app/Traits/Auditable.php
trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            AuditLog::create([
                'tenant_id' => tenant('id'),
                'user_id' => auth()->id(),
                'action' => 'created',
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'old_values' => null,
                'new_values' => $model->toArray(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        });
    }
}
```

#### **🔑 RBAC Implementation**

```php
// app/Services/PermissionService.php
class PermissionService
{
    public function hasPermission(User $user, string $permission): bool
    {
        $rolePermissions = Cache::remember(
            "role_permissions:{$user->role}",
            3600,
            fn() => Permission::where('role', $user->role)->pluck('permissions')->first()
        );

        return in_array($permission, $rolePermissions ?? []);
    }
}
```

### **🔄 Processamento Assíncrono**

#### **📋 Queue Configuration**

```php
// config/queue.php
'connections' => [
    'database' => [
        'connection' => 'mysql',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ],
],
```

#### **💼 Jobs Implementados**

```php
// app/Jobs/ProcessBudgetReport.php
class ProcessBudgetReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        // Processa relatório pesado em background
        $data = $this->budgetService->generateComplexReport($this->filters);
        $this->reportService->saveToStorage($data);
    }
}
```

### **📡 API Architecture**

#### **🔌 RESTful Endpoints**

```
API Versioning: /api/v1/
├── /auth (Authentication endpoints)
├── /customers (CRM operations)
├── /budgets (Financial operations)
├── /products (Product management)
├── /reports (Report generation)
└── /admin (Administrative functions)
```

#### **🔐 API Security**

```php
// app/Http/Middleware/ApiThrottleMiddleware.php
class ApiThrottleMiddleware
{
    public function handle($request, Closure $next)
    {
        $key = $request->ip() . '|' . $request->path();
        $maxAttempts = 60; // requests per minute
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'message' => 'Too many requests'
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);
        return $next($request);
    }
}
```

### **📊 Monitoramento e Observabilidade**

#### **🔍 Logging Strategy**

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'daily'],
    ],
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
        'days' => 30,
    ],
],
```

### **📊 Status da Migração**

-  **Arquitetura Base:** ✅ Implementada (Controller → Services → Repositories → Models)
-  **Multi-tenant:** ✅ Projetado e documentado
-  **Traits Essenciais:** ✅ TenantScoped e Auditable definidos
-  **Middleware:** 🔄 Em desenvolvimento
-  **Sistema de Cache:** ✅ Configurado (Redis)
-  **Processamento Assíncrono:** ✅ Estrutura preparada (Queue)

Este documento descreve a arquitetura técnica completa do Easy Budget Laravel, incluindo padrões utilizados, estrutura de código, fluxos críticos e estratégias de performance implementadas.
