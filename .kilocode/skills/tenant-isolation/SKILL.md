---
name: tenant-isolation
description: Garante o isolamento correto de dados multi-tenant em todas as operações do sistema.
---

# Isolamento Multi-Tenant do Easy Budget

Esta skill define as regras para garantir o isolamento correto de dados por tenant (empresa) em todas as operações do sistema Easy Budget.

## Arquitetura Multi-Tenant

```
🌐 Sistema Global
├── 🏢 Tenant A (Empresa 1)
│   ├── 👤 Provider (Dono da empresa)
│   │   ├── 👥 Clientes (isolados)
│   │   ├── 📦 Produtos (isolados)
│   │   ├── 💰 Orçamentos (isolados)
│   │   └── 📊 Dados financeiros (isolados)
│   └── 💾 Dados isolados da empresa
├── 🏢 Tenant B (Empresa 2)
│   └── 💾 Dados isolados da empresa
└── 🔐 Admin Global (Dono do Sistema)
    └── 📊 Métricas agregadas (sem dados sensíveis)
```

## Regras de Isolamento

### 1. Tenant Scoped via Trait

```php
<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;

trait TenantScoped
{
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (!$model->tenant_id) {
                $model->tenant_id = tenant('id');
            }
        });
    }
}
```

### 2. Repositories com Filtro Obrigatório

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ModelName;
use App\Models\Traits\TenantScoped;

class ModelNameRepository
{
    /**
     * Busca registro por ID e tenant ID.
     */
    public function findByIdAndTenantId(int $id, int $tenantId): ?ModelName
    {
        return ModelName::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Lista todos os registros do tenant.
     */
    public function getAllByTenantId(int $tenantId, array $filters = []): Collection
    {
        $query = ModelName::where('tenant_id', $tenantId);

        // Aplicar filtros adicionais
        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Verifica se registro pertence ao tenant.
     */
    public function belongsToTenant(int $id, int $tenantId): bool
    {
        return ModelName::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->exists();
    }
}
```

### 3. Controllers com Validação

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ModelService;
use App\Support\ServiceResult;

class ModelController extends Controller
{
    public function __construct(private ModelService $service) {}

    public function show(int $id): View
    {
        $result = $this->service->findById($id);

        if ($result->isError()) {
            abort(404, 'Registro não encontrado.');
        }

        return view('model.show', ['model' => $result->getData()]);
    }

    public function update(UpdateRequest $request, int $id): RedirectResponse
    {
        $result = $this->service->update($id, $request->validated());

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('model.index')
            ->with('success', 'Registro atualizado com sucesso.');
    }
}
```

### 4. Middleware de Tenant

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Verificar se há tenant definido na sessão
        $tenantId = session('tenant_id');

        if (!$tenantId && auth()->check()) {
            $tenantId = auth()->user()->tenant_id;
        }

        if (!$tenantId) {
            abort(403, 'Tenant não identificado.');
        }

        // Configurar tenant para a requisição
        config(['tenant.id' => $tenantId]);

        return $next($request);
    }
}
```

## Regras Críticas

### ✅ FAÇA

- Use `TenantScoped` trait em todos os modelos que precisam de isolamento
- Sempre passe `tenant_id` explicitamente em criações
- Valide a pertencimento do registro ao tenant antes de operações
- Use `tenant('id')` helper para obter o tenant atual

### ❌ NÃO FAÇA

- Nunca use `Model::all()` sem filtrar por tenant
- Não confie apenas em global scopes para operações críticas
- Não exponha IDs de registros de outros tenants
- Não忽略了验证租户所有权

## Verificação de Segurança

Ao revisar código, verifique:

```php
// ❌ Incorreto - Pode vazar dados de outros tenants
public function getProducts(): Collection
{
    return Product::all(); // Falta tenant_id
}

// ✅ Correto - Filtra por tenant
public function getProducts(): Collection
{
    return Product::where('tenant_id', tenant('id'))->get();
}
```

## Casos Especiais

### Dados Globais (não tenant-scoped)

```php
// Tabelas de sistema que não precisam de isolamento
class Plan extends Model
{
    // NÃO use TenantScoped
    // Estes dados são globais para todos os tenants
}

// Tabelas com tenant_id opcional
class AuditLog extends Model
{
    use TenantScoped; // Pode ter tenant_id nulo para logs globais
}
```
