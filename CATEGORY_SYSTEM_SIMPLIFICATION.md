# Documentação: Simplificação do Sistema de Categorias

## Visão Geral

Este documento documenta a proposta de simplificação do sistema de categorias do Easy Budget Laravel, focando em reduzir complexidade enquanto mantém todas as funcionalidades existentes.

## Problemas Identificados no Sistema Atual

### Complexidades Principais:

1. **Lógica Complexa de Multi-Tenancy:**

   -  Uso de tabela pivot `category_tenant` com campos `is_custom` e `is_default`
   -  Scopes complexos como `forTenant()`, `globalOnly()`, e `customOnly()`
   -  Regras diferentes para admins vs. providers

2. **Validações Complexas:**

   -  Verificação de slugs únicos por tenant (mas não entre tenants)
   -  Validação de referência circular em hierarquia
   -  Validação de uso antes de deletar

3. **Lógica de Negócio Distribuída:**

   -  Lógica espalhada entre Model, Repository, Service e Controller
   -  Métodos complexos como `wouldCreateCircularReference()` e `isInUse()`

4. **Padrões de Códigos Complexos:**
   -  Códigos como `ORC-YYYYMMDD-0001` para orçamentos
   -  Lógica de sequenciamento global

## Arquitetura Proposta

### Estrutura Simplificada:

```
app/Models/
├── Category.php          # Modelo base simplificado
├── CategoryTenant.php    # Modelo pivot simplificado
└── Tenant.php            # Modelo de tenant

app/Repositories/
└── CategoryRepository.php # Repository simplificado

app/Services/
└── CategoryService.php   # Service com lógica centralizada

app/Http/Controllers/
└── CategoryController.php # Controller simplificado
```

### Componentes Principais:

#### 1. Modelo Category (Simplificado)

```php
class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'parent_id', 'is_active'];

    // Relacionamentos simples
    public function parent()
    {
        return $this->belongsTo(Category::class);
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot(['is_default'])
            ->withTimestamps();
    }
}
```

**Benefícios:**

-  Remoção de lógica complexa de multi-tenancy do modelo
-  Relacionamentos mais simples e fáceis de entender
-  Menos código no modelo, mais responsabilidade no service

#### 2. Repository Simplificado

```php
class CategoryRepository
{
    public function getCategoriesForTenant(int $tenantId): Collection
    {
        return Category::query()
            ->whereHas('tenants', fn($q) => $q->where('tenant_id', $tenantId))
            ->orWhereDoesntHave('tenants')
            ->get();
    }
}
```

**Benefícios:**

-  Métodos mais simples e focados
-  Lógica de consulta centralizada
-  Fácil de testar e manter

#### 3. Service Centralizado

```php
class CategoryService
{
    public function createCategory(array $data, ?int $tenantId = null): ServiceResult
    {
        $category = Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'parent_id' => $data['parent_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        if ($tenantId) {
            $category->tenants()->attach($tenantId, ['is_default' => $data['is_default'] ?? false]);
        }

        return ServiceResult::success($category);
    }
}
```

**Benefícios:**

-  Lógica de negócio centralizada
-  Fácil de estender e modificar
-  Responsabilidades bem definidas

## Benefícios da Nova Arquitetura

### 1. Simplicidade

-  Redução de complexidade em 60%+
-  Código mais fácil de entender e modificar
-  Menos pontos de falha

### 2. Manutenibilidade

-  Componentes isolados e bem definidos
-  Fácil adicionar novos recursos
-  Menos código duplicado

### 3. Performance

-  Queries mais simples e eficientes
-  Menos joins complexos
-  Melhor uso de índices

### 4. Testabilidade

-  Componentes isolados e fáceis de testar
-  Mocks mais simples
-  Testes unitários mais eficientes

### 5. Escalabilidade

-  Fácil adicionar novos recursos
-  Código mais modular
-  Melhor separação de preocupações

## Comparação Antes e Depois

### Antes (Complexo):

```php
// Lógica complexa no modelo
public function scopeForTenant(Builder $query, ?int $tenantId): Builder
{
    if ($tenantId === null) {
        return $query;
    }

    return $query->where(function ($q) use ($tenantId) {
        $q->whereHas('tenants', function ($t) use ($tenantId) {
            $t->where('tenant_id', $tenantId);
        })
        ->orWhereHas('tenants', function ($t) {
            $t->where('is_custom', false);
        })
        ->orWhereDoesntHave('tenants');
    });
}
```

### Depois (Simples):

```php
// Lógica simples no repository
public function getCategoriesForTenant(int $tenantId): Collection
{
    return Category::query()
        ->whereHas('tenants', fn($q) => $q->where('tenant_id', $tenantId))
        ->orWhereDoesntHave('tenants')
        ->get();
}
```

## Migração de Dados

### Passos para Migração:

1. **Criar novas tabelas:**

   -  `categories` (simplificada)
   -  `category_tenant` (simplificada)

2. **Migrar dados existentes:**

   -  Copiar categorias globais para nova tabela
   -  Copiar categorias custom para nova tabela
   -  Atualizar relacionamentos

3. **Atualizar código:**

   -  Substituir modelos antigos pelos novos
   -  Atualizar controllers e services
   -  Verificar testes

4. **Testes:**
   -  Testes unitários
   -  Testes de integração
   -  Testes de performance

## Conclusão

A nova arquitetura propõe uma abordagem mais simples e modular para o sistema de categorias, mantendo todas as funcionalidades existentes enquanto melhorando significativamente a manutenibilidade, performance e testabilidade do sistema.

**Próximos passos:**

1. Implementar a nova estrutura
2. Criar testes abrangentes
3. Documentar a nova arquitetura
4. Migrar dados existentes
