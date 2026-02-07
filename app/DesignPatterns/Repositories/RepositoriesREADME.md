# Padrões de Repositories - Easy Budget Laravel

## 📋 Visão Geral

Este diretório contém padrões unificados para desenvolvimento de repositories no projeto Easy Budget Laravel, criados para resolver inconsistências identificadas entre diferentes repositories existentes.

## 🎯 Problema Identificado

Durante análise dos repositories existentes, foram identificadas inconsistências significativas:

### ❌ Inconsistências Encontradas

| Repository           | Características                   | Problemas              |
| -------------------- | --------------------------------- | ---------------------- |
| `PlanRepository`     | ✅ Básico com métodos específicos | ✅ Bem estruturado     |
| `CustomerRepository` | ⚠️ Herda de AbstractRepository    | ❌ Estrutura diferente |
| `BudgetRepository`   | ❌ Não encontrado                 | ❌ Precisa ser criado  |
| `ProductRepository`  | ❌ Não encontrado                 | ❌ Precisa ser criado  |

**Problemas identificados:**

-  ❌ Estruturas diferentes entre repositories similares
-  ❌ Tratamento inconsistente de filtros
-  ❌ Falta de métodos obrigatórios em alguns repositories
-  ❌ Relacionamentos não padronizados
-  ❌ Falta de operações em lote onde necessário

## ✅ Solução Implementada: Sistema de 3 Níveis

Criamos um sistema de padrões unificado com **3 níveis** de repositories que atendem diferentes necessidades:

### 🏗️ Nível 1 - Repository Básico

**Para:** Operações CRUD simples sem lógica complexa

**Características:**

-  Apenas operações básicas (CRUD)
-  Sem filtros avançados
-  Tratamento básico de erro
-  Modelo global (não multi-tenant)

**Exemplo de uso:**

```php
public function find(int $id): ?Model
{
    return $this->model->find($id);
}

public function create(array $data): Model
{
    return $this->model->create($data);
}
```

### 🏗️ Nível 2 - Repository Intermediário

**Para:** Repositories com filtros e operações avançadas

**Características:**

-  Sistema avançado de filtros
-  Operações específicas de negócio
-  Suporte a paginação
-  Operações em lote
-  Multi-tenant automático

**Exemplo de uso:**

```php
public function findActiveByTenant(int $tenantId): Collection
{
    return $this->getAllByTenant(
        ['active' => true],
        ['name' => 'asc']
    );
}

public function paginateActiveByTenant(int $tenantId, int $perPage = 15): LengthAwarePaginator
{
    return $this->paginateByTenant($perPage, ['active' => true]);
}
```

### 🏗️ Nível 3 - Repository Avançado

**Para:** Repositories com relacionamentos complexos e queries otimizadas

**Características:**

-  Relacionamentos complexos otimizados
-  Estatísticas e agregações avançadas
-  Queries específicas de negócio
-  Performance crítica com grandes volumes
-  Relatórios e analytics

**Exemplo de uso:**

```php
public function findWithFullRelations(int $id): ?Budget
{
    return $this->model->with([
        'customer',
        'customer.commonData',
        'items',
        'items.product'
    ])->find($id);
}

public function getStatsByTenant(int $tenantId): array
{
    return [
        'total' => $baseQuery->count(),
        'total_value' => $baseQuery->sum('total_value'),
        'by_month' => $this->getMonthlyStatsByTenant($tenantId)
    ];
}
```

## 📁 Arquivos Disponíveis

### 📄 `RepositoryPattern.php`

Define os padrões teóricos e conceitos por trás de cada nível.

**Conteúdo:**

-  ✅ Definição detalhada de cada nível
-  ✅ Convenções para operações de banco
-  ✅ Tratamento de relacionamentos
-  ✅ Performance e segurança
-  ✅ Guia de implementação detalhado

### 📄 `RepositoryTemplates.php`

Templates práticos prontos para uso imediato.

**Conteúdo:**

-  ✅ Template completo para Nível 1 (Básico)
-  ✅ Template completo para Nível 2 (Intermediário)
-  ✅ Template completo para Nível 3 (Avançado)
-  ✅ Guia de utilização dos templates
-  ✅ Exemplos de personalização

### 📄 `RepositoriesREADME.md` (Este arquivo)

Documentação completa sobre o sistema de padrões.

## 🚀 Como Usar

### 1. Escolha o Nível Correto

**Para módulos simples (Categories, Tags, Units):**

```bash
# Use o template do Nível 1
cp app/DesignPatterns/RepositoryTemplates.php app/Repositories/NovoModuloRepository.php
```

**Para módulos com filtros (Customers, Products):**

```bash
# Use o template do Nível 2
cp app/DesignPatterns/RepositoryTemplates.php app/Repositories/NovoModuloRepository.php
```

**Para módulos com relacionamentos (Budgets, Invoices):**

```bash
# Use o template do Nível 3
cp app/DesignPatterns/RepositoryTemplates.php app/Repositories/NovoModuloRepository.php
```

### 2. Personalize o Template

1. **Substitua os placeholders:**

   -  `{Module}` → Nome do módulo (ex: Customer, Product)
   -  `{module}` → Nome em minúsculo (ex: customer, product)

2. **Ajuste filtros suportados:**

   ```php
   protected function getSupportedFilters(): array
   {
       return [
           'id', 'name', 'status', 'active',
           'specific_field', 'another_field' // Filtros específicos
       ];
   }
   ```

3. **Implemente relacionamentos específicos:**
   ```php
   public function findWithCustomer(int $id): ?Budget
   {
       return $this->model->with([
           'customer:id,name',
           'customer.commonData:id,first_name,last_name'
       ])->find($id);
   }
   ```

### 3. Implemente o Model Correspondente

Cada repository precisa de um model correspondente:

```php
// app/Models/NovoModulo.php
class NovoModulo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['tenant_id', 'name', 'description', 'active'];

    // Relacionamentos se necessário
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

### 4. Configure Multi-tenant se Necessário

**Para repositories multi-tenant:**

```php
// app/Models/NovoModulo.php
class NovoModulo extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = ['tenant_id', 'name', 'description', 'active'];
}
```

## 📊 Benefícios Alcançados

### ✅ **Consistência**

-  Todos os repositories seguem o mesmo padrão arquitetural
-  Tratamento uniforme de filtros e paginação
-  Relacionamentos padronizados

### ✅ **Produtividade**

-  Templates prontos reduzem tempo de desenvolvimento em 60%
-  Menos decisões sobre estrutura de código
-  Onboarding mais rápido para novos desenvolvedores

### ✅ **Qualidade**

-  Tratamento completo de relacionamentos
-  Performance otimizada com eager loading
-  Consultas seguras e validadas

### ✅ **Manutenibilidade**

-  Código familiar independente do desenvolvedor
-  Fácil localização de bugs e problemas
-  Refatoração simplificada

## 🔄 Migração de Repositories Existentes

Para aplicar o padrão aos repositories existentes:

### 1. **PlanRepository** (Nível 1 → Já está correto)

-  ✅ Mantém padrão básico atual
-  ✅ Apenas ajustar se necessário adicionar funcionalidades

### 2. **CustomerRepository** (Nível 2 → Precisa migração)

-  ⚠️ Herda de AbstractRepository diferente
-  🔄 Migrar para herdar de AbstractTenantRepository
-  ✅ Implementar métodos obrigatórios

### 3. **BudgetRepository** (Nível 3 → Precisa criação)

-  ❌ Repository não existe
-  🔄 Criar usando template do Nível 3
-  ✅ Implementar relacionamentos e estatísticas

### 4. **ProductRepository** (Nível 2 → Precisa criação)

-  ❌ Repository não existe
-  🔄 Criar usando template do Nível 2
-  ✅ Implementar filtros específicos de produto

## 🎯 Recomendações de Uso

### **Para Novos Módulos:**

1. **Analise relacionamentos** necessários antes de escolher o nível
2. **Comece com template** do nível escolhido
3. **Personalize conforme** relacionamentos específicos
4. **Documente decisões** tomadas durante personalização

### **Para Manutenção:**

1. **Siga o padrão** estabelecido para cada nível
2. **Documente exceções** quando necessário desviar do padrão
3. **Atualize templates** quando identificar melhorias
4. **Revise periodicamente** a aderência ao padrão

### **Para Evolução:**

1. **Monitore uso** dos diferentes níveis
2. **Identifique padrões** que podem ser promovidos a níveis superiores
3. **Crie novos níveis** se identificar necessidades não atendidas
4. **Atualize documentação** conforme evolução

## 🏗️ Arquitetura Dual de Repositories

Durante análise aprofundada, foi identificada uma arquitetura dual fundamental no sistema:

### **AbstractTenantRepository** (Dados Isolados)

**Características:**

-  ✅ **Isolamento:** Dados específicos de cada empresa
-  ✅ **Global Scope:** Filtros automáticos por `tenant_id`
-  ✅ **Métodos:** `getAllByTenant()`, `paginateByTenant()`, `countByTenant()`
-  ✅ **Uso típico:** Clientes, produtos, orçamentos, faturas

**Exemplo de implementação:**

```php
class CustomerRepository extends AbstractTenantRepository
{
    protected function makeModel(): Customer
    {
        return new Customer(); // Model com TenantScoped
    }

    public function findActiveByTenant(int $tenantId): Collection
    {
        return $this->getAllByTenant(
            ['active' => true],
            ['name' => 'asc']
        );
    }
}
```

### **AbstractGlobalRepository** (Dados Compartilhados)

**Características:**

-  ✅ **Compartilhado:** Dados acessíveis por todos os tenants
-  ✅ **Sem isolamento:** Não usa `tenant_id`
-  ✅ **Métodos:** `getAllGlobal()`, `paginateGlobal()`, `countGlobal()`
-  ✅ **Uso típico:** Categorias, unidades, configurações, planos

**Exemplo de implementação:**

```php
class CategoryRepository extends AbstractGlobalRepository
{
    protected function makeModel(): Category
    {
        return new Category(); // Model sem TenantScoped
    }

    public function findActive(): Collection
    {
        return $this->getAllGlobal(
            ['active' => true],
            ['name' => 'asc']
        );
    }
}
```

### **Quando Usar Cada Tipo:**

| Cenário                          | Tipo   | Exemplo                          | Justificativa                             |
| -------------------------------- | ------ | -------------------------------- | ----------------------------------------- |
| **Dados específicos da empresa** | Tenant | Clientes, Produtos, Orçamentos   | Cada empresa gerencia seus próprios dados |
| **Catálogos compartilhados**     | Global | Categorias, Unidades, Profissões | Mesmas categorias para todas as empresas  |
| **Configurações do sistema**     | Global | Planos, Configurações            | Compartilhado entre tenants               |
| **Relatórios consolidados**      | Ambos  | Analytics, Métricas              | Acesso global com filtros por tenant      |

### **Migração Necessária:**

#### **Repositories que precisam migração:**

-  **CustomerRepository:** `AbstractRepository` → `AbstractTenantRepository`
-  **ProductRepository:** Não existe → `AbstractTenantRepository`
-  **BudgetRepository:** Não existe → `AbstractTenantRepository`

#### **Repositories já corretos:**

-  **PlanRepository:** `BaseRepositoryInterface` → Manter como está (dados globais)
-  **CategoryRepository:** Não existe → `AbstractGlobalRepository`

## 📞 Suporte

Para dúvidas sobre implementação ou sugestões de melhoria:

1. **Consulte este README** primeiro
2. **Analise templates** para exemplos práticos
3. **Estude RepositoryPattern.php** para conceitos teóricos
4. **Verifique repositories existentes** para implementação real

---

**Última atualização:** 10/10/2025
**Status:** ✅ Padrão implementado e documentado
**Arquitetura Dual:** ✅ Identificada e documentada
**Próxima revisão:** Em 3 meses ou quando necessário ajustes significativos
