<?php

declare(strict_types=1);

namespace App\DesignPatterns;

/**
 * Templates Práticos para Repositories
 *
 * Fornece templates prontos para uso imediato no desenvolvimento,
 * seguindo o padrão unificado definido em RepositoryPattern.
 *
 * @package App\DesignPatterns
 */
class RepositoryTemplates
{
    /**
     * TEMPLATE COMPLETO - Repository Nível 1 (Básico)
     */
    public function basicRepositoryTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\{Module};
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository básico para {Module} - Apenas operações CRUD
 *
 * Implementa operações básicas sem lógica complexa ou filtros avançados.
 */
class {Module}Repository implements BaseRepositoryInterface
{
    protected {Module} $model;

    /**
     * Construtor - Inicializa o modelo
     */
    public function __construct()
    {
        $this->model = new {Module}();
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?{Module}
    {
        return $this->model->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): {Module}
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): ?{Module}
    {
        $model = $this->find($id);

        if (!$model) {
            return null;
        }

        $model->update($data);
        return $model->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $model = $this->find($id);

        if (!$model) {
            return false;
        }

        return $model->delete();
    }

    // --------------------------------------------------------------------------
    // MÉTODOS ESPECÍFICOS BÁSICOS
    // --------------------------------------------------------------------------

    /**
     * Busca {module} ativos
     */
    public function findActive(): Collection
    {
        return $this->model->where(\'active\', true)->get();
    }

    /**
     * Busca {module} por slug
     */
    public function findBySlug(string $slug): ?{Module}
    {
        return $this->model->where(\'slug\', $slug)->first();
    }

    /**
     * Busca {module} ordenados por nome
     */
    public function findOrderedByName(string $direction = \'asc\'): Collection
    {
        return $this->model->orderBy(\'name\', $direction)->get();
    }

    /**
     * Conta total de {module}
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Verifica se existe {module} com determinado ID
     */
    public function exists(int $id): bool
    {
        return $this->model->where(\'id\', $id)->exists();
    }

    /**
     * Busca múltiplos {module} por IDs
     */
    public function findMany(array $ids): Collection
    {
        return $this->model->whereIn(\'id\', $ids)->get();
    }
}';
    }

    /**
     * TEMPLATE COMPLETO - Repository Nível 2 (Intermediário)
     */
    public function intermediateRepositoryTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\{Module};
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repository intermediário para {Module} - Com filtros e operações avançadas
 *
 * Implementa funcionalidades avançadas como filtros, paginação e validações.
 */
class {Module}Repository extends AbstractTenantRepository
{
    /**
     * Define o modelo para este repository
     */
    protected function makeModel(): {Module}
    {
        return new {Module}();
    }

    /**
     * Retorna lista de filtros suportados
     */
    protected function getSupportedFilters(): array
    {
        return [
            \'id\',
            \'name\',
            \'slug\',
            \'status\',
            \'active\',
            \'created_at\',
            \'updated_at\',
        ];
    }

    /**
     * Retorna lista de campos ordenáveis
     */
    protected function getSortableFields(): array
    {
        return [
            \'id\',
            \'name\',
            \'slug\',
            \'created_at\',
            \'updated_at\',
        ];
    }

    // --------------------------------------------------------------------------
    // MÉTODOS ESPECÍFICOS DE NEGÓCIO
    // --------------------------------------------------------------------------

    /**
     * Busca {module} ativos por tenant
     */
    public function findActiveByTenant(int $tenantId): Collection
    {
        return $this->getAllByTenant(
            [\'active\' => true],
            [\'name\' => \'asc\']
        );
    }

    /**
     * Busca {module} por status e tenant
     */
    public function findByStatusAndTenant(int $tenantId, string $status): Collection
    {
        return $this->getAllByTenant(
            [\'status\' => $status],
            [\'created_at\' => \'desc\']
        );
    }

    /**
     * Busca {module} por categoria e tenant
     */
    public function findByCategoryAndTenant(int $tenantId, int $categoryId): Collection
    {
        return $this->getAllByTenant([
            \'category_id\' => $categoryId,
            \'active\' => true
        ], [\'name\' => \'asc\']);
    }

    /**
     * Pagina {module} ativos por tenant
     */
    public function paginateActiveByTenant(int $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateByTenant($perPage, [\'active\' => true]);
    }

    /**
     * Conta {module} ativos por tenant
     */
    public function countActiveByTenant(int $tenantId): int
    {
        return $this->countByTenant([\'active\' => true]);
    }

    /**
     * Busca {module} recentes por tenant
     */
    public function findRecentByTenant(int $tenantId, int $days = 7): Collection
    {
        return $this->getAllByTenant([
            \'created_at\' => [
                \'operator\' => \'>\',
                \'value\' => now()->subDays($days)->toDateString()
            ]
        ], [\'created_at\' => \'desc\'], 50);
    }

    /**
     * Busca {module} por faixa de preço e tenant
     */
    public function findByPriceRangeAndTenant(
        int $tenantId,
        float $minPrice,
        float $maxPrice
    ): Collection {
        return $this->getAllByTenant([
            \'price\' => [
                \'operator\' => \'between\',
                \'value\' => [$minPrice, $maxPrice]
            ],
            \'active\' => true
        ], [\'price\' => \'asc\']);
    }

    /**
     * Atualiza múltiplos {module} por tenant
     */
    public function bulkUpdateByTenant(
        int $tenantId,
        array $criteria,
        array $data
    ): int {
        return $this->bulkUpdateByTenant($tenantId, $criteria, $data);
    }

    /**
     * Remove múltiplos {module} por tenant
     */
    public function bulkDeleteByTenant(
        int $tenantId,
        array $criteria
    ): int {
        return $this->bulkDeleteByTenant($tenantId, $criteria);
    }

    /**
     * Busca {module} com relacionamentos específicos
     */
    public function findWithRelations(int $id, array $relations = []): ?{Module}
    {
        if (empty($relations)) {
            return $this->find($id);
        }

        return $this->model->with($relations)->find($id);
    }

    /**
     * Busca todos {module} com relacionamentos por tenant
     */
    public function getAllWithRelationsByTenant(
        int $tenantId,
        array $relations = [],
        array $criteria = []
    ): Collection {
        $query = $this->model->with($relations);

        if (!empty($criteria)) {
            $this->applyFilters($query, $criteria);
        }

        return $query->get();
    }
}';
    }

    /**
     * TEMPLATE COMPLETO - Repository Nível 3 (Avançado)
     */
    public function advancedRepositoryTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\{Module};
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Repository avançado para {Module} - Com relacionamentos e queries complexas
 *
 * Implementa operações avançadas com relacionamentos, estatísticas e agregações.
 */
class {Module}Repository extends AbstractTenantRepository
{
    /**
     * Define o modelo para este repository
     */
    protected function makeModel(): {Module}
    {
        return new {Module}();
    }

    /**
     * Retorna lista de filtros suportados
     */
    protected function getSupportedFilters(): array
    {
        return [
            \'id\',
            \'name\',
            \'status\',
            \'type\',
            \'category_id\',
            \'customer_id\',
            \'total_value\',
            \'created_at\',
            \'updated_at\',
        ];
    }

    /**
     * Retorna lista de campos ordenáveis
     */
    protected function getSortableFields(): array
    {
        return [
            \'id\',
            \'name\',
            \'total_value\',
            \'created_at\',
            \'updated_at\',
        ];
    }

    // --------------------------------------------------------------------------
    // QUERIES OTIMIZADAS COM RELACIONAMENTOS
    // --------------------------------------------------------------------------

    /**
     * Busca {module} com relacionamentos completos
     */
    public function findWithFullRelations(int $id): ?{Module}
    {
        return $this->model->with([
            \'customer\',
            \'customer.commonData\',
            \'customer.contact\',
            \'items\',
            \'items.product\',
            \'items.unit\'
        ])->find($id);
    }

    /**
     * Busca todos {module} com relacionamentos por tenant
     */
    public function getAllWithRelationsByTenant(
        int $tenantId,
        array $relations = []
    ): Collection {
        $defaultRelations = [
            \'customer:id,name\',
            \'statusInfo:id,name,color\'
        ];

        $relations = array_merge($defaultRelations, $relations);

        return $this->model->with($relations)
            ->where(\'tenant_id\', $tenantId)
            ->get();
    }

    /**
     * Pagina {module} com detalhes completos por tenant
     */
    public function paginateWithFullDetails(
        int $tenantId,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->model->with([
            \'customer:id,name\',
            \'customer.commonData:id,first_name,last_name\',
            \'items:id,{module}_id,product_id,quantity,unit_price,total\',
            \'items.product:id,name\',
            \'statusInfo:id,name,color\'
        ])
        ->where(\'tenant_id\', $tenantId)
        ->paginate($perPage);
    }

    // --------------------------------------------------------------------------
    // QUERIES ESPECÍFICAS DE NEGÓCIO
    // --------------------------------------------------------------------------

    /**
     * Busca {module} por cliente e tenant
     */
    public function findByCustomerAndTenant(int $customerId, int $tenantId): Collection
    {
        return $this->getAllByTenant([
            \'customer_id\' => $customerId
        ], [\'created_at\' => \'desc\']);
    }

    /**
     * Busca {module} em atraso por tenant
     */
    public function findOverdueByTenant(int $tenantId): Collection
    {
        return $this->getAllByTenant([
            \'status\' => \'pending\',
            \'due_date\' => [
                \'operator\' => \'<\',
                \'value\' => now()->toDateString()
            ]
        ], [\'due_date\' => \'asc\']);
    }

    /**
     * Busca {module} por período e tenant
     */
    public function findByDateRangeAndTenant(
        int $tenantId,
        string $startDate,
        string $endDate
    ): Collection {
        return $this->getAllByTenant([
            \'created_at\' => [
                \'operator\' => \'between\',
                \'value\' => [$startDate, $endDate]
            ]
        ], [\'created_at\' => \'desc\']);
    }

    /**
     * Busca {module} por faixa de valor e tenant
     */
    public function findByValueRangeAndTenant(
        int $tenantId,
        float $minValue,
        float $maxValue
    ): Collection {
        return $this->getAllByTenant([
            \'total_value\' => [
                \'operator\' => \'between\',
                \'value\' => [$minValue, $maxValue]
            ]
        ], [\'total_value\' => \'desc\']);
    }

    // --------------------------------------------------------------------------
    // ESTATÍSTICAS AVANÇADAS
    // --------------------------------------------------------------------------

    /**
     * Obtém estatísticas completas por tenant
     */
    public function getStatsByTenant(int $tenantId): array
    {
        $baseQuery = $this->model->where(\'tenant_id\', $tenantId);

        return [
            \'total\' => $baseQuery->count(),
            \'approved\' => (clone $baseQuery)->where(\'status\', \'approved\')->count(),
            \'pending\' => (clone $baseQuery)->where(\'status\', \'pending\')->count(),
            \'rejected\' => (clone $baseQuery)->where(\'status\', \'rejected\')->count(),
            \'total_value\' => $baseQuery->sum(\'total_value\'),
            \'average_value\' => $baseQuery->avg(\'total_value\'),
            \'by_month\' => $this->getMonthlyStatsByTenant($tenantId),
            \'by_status\' => $this->getStatusDistributionByTenant($tenantId),
        ];
    }

    /**
     * Obtém estatísticas mensais por tenant
     */
    private function getMonthlyStatsByTenant(int $tenantId): array
    {
        return $this->model->select(
                DB::raw(\'MONTH(created_at) as month\'),
                DB::raw(\'COUNT(*) as count\'),
                DB::raw(\'SUM(total_value) as total_value\')
            )
            ->where(\'tenant_id\', $tenantId)
            ->whereYear(\'created_at\', now()->year)
            ->groupBy(\'month\')
            ->orderBy(\'month\')
            ->pluck(\'total_value\', \'month\')
            ->toArray();
    }

    /**
     * Obtém distribuição por status por tenant
     */
    private function getStatusDistributionByTenant(int $tenantId): array
    {
        return $this->model->select(\'status\', DB::raw(\'COUNT(*) as count\'))
            ->where(\'tenant_id\', $tenantId)
            ->groupBy(\'status\')
            ->pluck(\'count\', \'status\')
            ->toArray();
    }

    /**
     * Obtém top clientes por quantidade de {module}
     */
    public function getTopCustomersByCount(int $tenantId, int $limit = 10): Collection
    {
        return $this->model->select(\'customer_id\', DB::raw(\'COUNT(*) as {module}_count\'))
            ->with(\'customer:id,name\')
            ->where(\'tenant_id\', $tenantId)
            ->groupBy(\'customer_id\')
            ->orderByDesc(\'{module}_count\')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém receita mensal por tenant
     */
    public function getMonthlyRevenueByTenant(int $tenantId, int $year): Collection
    {
        return $this->model->select(
                DB::raw(\'MONTH(created_at) as month\'),
                DB::raw(\'SUM(total_value) as revenue\'),
                DB::raw(\'COUNT(*) as count\')
            )
            ->where(\'tenant_id\', $tenantId)
            ->whereYear(\'created_at\', $year)
            ->where(\'status\', \'approved\')
            ->groupBy(\'month\')
            ->orderBy(\'month\')
            ->get();
    }

    // --------------------------------------------------------------------------
    // OPERAÇÕES DE AGREGAÇÃO
    // --------------------------------------------------------------------------

    /**
     * Obtém soma de valores por tenant
     */
    public function getTotalValueByTenant(int $tenantId, array $criteria = []): float
    {
        $query = $this->model->where(\'tenant_id\', $tenantId);

        if (!empty($criteria)) {
            $this->applyFilters($query, $criteria);
        }

        return $query->sum(\'total_value\');
    }

    /**
     * Obtém média de valores por tenant
     */
    public function getAverageValueByTenant(int $tenantId, array $criteria = []): float
    {
        $query = $this->model->where(\'tenant_id\', $tenantId);

        if (!empty($criteria)) {
            $this->applyFilters($query, $criteria);
        }

        return $query->avg(\'total_value\');
    }

    /**
     * Obtém {module} com itens e produtos relacionados
     */
    public function findWithItemsAndProducts(int $id): ?{Module}
    {
        return $this->model->with([
            \'items\' => function ($query) {
                $query->select(\'id\', \'{module}_id\', \'product_id\', \'quantity\', \'unit_price\', \'total\');
            },
            \'items.product\' => function ($query) {
                $query->select(\'id\', \'name\', \'description\');
            }
        ])->find($id);
    }

    /**
     * Obtém {module} pendentes de aprovação por tenant
     */
    public function getPendingApprovalByTenant(int $tenantId): Collection
    {
        return $this->model->with([
            \'customer:id,name\',
            \'items:id,{module}_id,product_id,quantity,total\'
        ])
        ->where(\'tenant_id\', $tenantId)
        ->where(\'status\', \'pending\')
        ->orderBy(\'created_at\', \'asc\')
        ->get();
    }

    /**
     * Obtém {module} recentes por tenant
     */
    public function getRecentByTenant(int $tenantId, int $days = 7): Collection
    {
        return $this->getAllByTenant([
            \'created_at\' => [
                \'operator\' => \'>\',
                \'value\' => now()->subDays($days)->toDateString()
            ]
        ], [\'created_at\' => \'desc\'], 50);
    }

    /**
     * Busca {module} com filtros complexos
     */
    public function findWithComplexFilters(
        int $tenantId,
        array $filters = []
    ): Collection {
        $query = $this->model->where(\'tenant_id\', $tenantId);

        // Filtros básicos
        if (isset($filters[\'status\'])) {
            $query->where(\'status\', $filters[\'status\']);
        }

        if (isset($filters[\'customer_id\'])) {
            $query->where(\'customer_id\', $filters[\'customer_id\']);
        }

        // Filtros de data
        if (isset($filters[\'date_from\'])) {
            $query->where(\'created_at\', \'>=\', $filters[\'date_from\']);
        }

        if (isset($filters[\'date_to\'])) {
            $query->where(\'created_at\', \'<=\', $filters[\'date_to\']);
        }

        // Filtros de valor
        if (isset($filters[\'min_value\'])) {
            $query->where(\'total_value\', \'>=\', $filters[\'min_value\']);
        }

        if (isset($filters[\'max_value\'])) {
            $query->where(\'total_value\', \'<=\', $filters[\'max_value\']);
        }

        return $query->with([\'customer\', \'items\'])
            ->orderBy($filters[\'order_by\'] ?? \'created_at\', $filters[\'direction\'] ?? \'desc\')
            ->get();
    }
}';
    }

    /**
     * GUIA DE UTILIZAÇÃO DOS TEMPLATES
     */
    public function getUsageGuide(): string
    {
        return '
## Como Usar os Templates de Repositories

### 1. Escolha o Nível Correto

**Nível 1 (Básico):**
- Para entidades simples sem relacionamentos
- Operações CRUD básicas suficientes
- Não há necessidade de filtros avançados
- Modelo global (não multi-tenant)

**Nível 2 (Intermediário):**
- Para entidades com necessidade de filtros
- Multi-tenant necessário
- Operações específicas além do CRUD básico
- Relacionamentos simples necessários

**Nível 3 (Avançado):**
- Para entidades com relacionamentos complexos
- Estatísticas e relatórios necessários
- Queries de agregação importantes
- Performance crítica com grandes volumes

### 2. Substitua os Placeholders

No template, substitua:
- `{Module}` → Nome do módulo (ex: Customer, Product, Budget)
- `{module}` → Nome em minúsculo (ex: customer, product, budget)

### 3. Personalize conforme Necessário

**Para Nível 1:**
```php
// Adicione métodos específicos básicos
public function findByCode(string $code): ?Category
{
    return $this->model->where(\'code\', $code)->first();
}

public function findOrderedByPriority(string $direction = \'asc\'): Collection
{
    return $this->model->orderBy(\'priority\', $direction)->get();
}
```

**Para Nível 2:**
```php
protected function getSupportedFilters(): array
{
    return [
        \'id\', \'name\', \'status\', \'active\',
        \'priority\', \'category_id\', // Filtros específicos
        \'created_at\', \'updated_at\'
    ];
}

public function findByPriorityAndTenant(int $tenantId, int $priority): Collection
{
    return $this->getAllByTenant([
        \'priority\' => $priority,
        \'active\' => true
    ], [\'name\' => \'asc\']);
}
```

**Para Nível 3:**
```php
public function getTopCustomersByBudgetValue(int $tenantId, int $limit = 10): Collection
{
    return $this->model->select(\'customer_id\', DB::raw(\'SUM(total_value) as total\'))
        ->with(\'customer:id,name\')
        ->where(\'tenant_id\', $tenantId)
        ->where(\'status\', \'approved\')
        ->groupBy(\'customer_id\')
        ->orderByDesc(\'total\')
        ->limit($limit)
        ->get();
}
```

### 4. Implemente Relacionamentos Específicos

**Para relacionamentos importantes:**
```php
public function findWithCustomerAndItems(int $budgetId): ?Budget
{
    return $this->model->with([
        \'customer\' => function ($query) {
            $query->select(\'id\', \'name\');
        },
        \'items\' => function ($query) {
            $query->select(\'id\', \'budget_id\', \'product_id\', \'quantity\', \'total\');
        },
        \'items.product\' => function ($query) {
            $query->select(\'id\', \'name\');
        }
    ])->find($budgetId);
}
```

### 5. Configure Índices de Performance

**Para campos frequentemente filtrados:**
```php
// Em migrations
Schema::table(\'{module}\', function (Blueprint $table) {
    $table->index([\'tenant_id\', \'status\']);
    $table->index([\'tenant_id\', \'created_at\']);
    $table->index([\'tenant_id\', \'customer_id\']);
});
```

## Benefícios dos Templates

✅ **Rapidez**: Criação rápida de repositories padronizados
✅ **Consistência**: Todos seguem convenções unificadas
✅ **Performance**: Queries otimizadas inclusas
✅ **Flexibilidade**: Diferentes níveis para diferentes necessidades
✅ **Manutenibilidade**: Estrutura clara e fácil de entender

## Estrutura de Arquivos Recomendada

```
app/Repositories/
├── Contracts/                          # Interfaces
│   ├── BaseRepositoryInterface.php    # Interface básica
│   └── TenantRepositoryInterface.php  # Interface multi-tenant
├── Abstracts/                         # Classes abstratas
│   ├── AbstractBaseRepository.php     # Base para todos
│   └── AbstractTenantRepository.php   # Base para multi-tenant
└── Repositories concretos/            # Implementações específicas
    ├── BasicRepository.php           # Nível 1 - Básico
    ├── IntermediateRepository.php    # Nível 2 - Intermediário
    └── AdvancedRepository.php        # Nível 3 - Avançado
```

## Convenções de Desenvolvimento

### **Métodos Obrigatórios:**
```php
public function find(int $id): ?Model
public function getAll(): Collection
public function create(array $data): Model
public function update(int $id, array $data): ?Model
public function delete(int $id): bool
```

### **Para Multi-tenant:**
```php
// Sempre herdar de AbstractTenantRepository
class CustomerRepository extends AbstractTenantRepository
{
    protected function makeModel(): Customer
    {
        return new Customer();
    }
}
```

### **Queries com Relacionamentos:**
```php
// ✅ Correto - Eager loading eficiente
public function findWithRelations(int $id, array $relations = []): ?Model
{
    if (empty($relations)) {
        return $this->find($id);
    }

    return $this->model->with($relations)->find($id);
}

// ✅ Correto - Relacionamentos específicos
public function findWithCustomer(int $id): ?Budget
{
    return $this->model->with([
        \'customer:id,name\',
        \'customer.commonData:id,first_name,last_name\'
    ])->find($id);
}
```

### **Filtros Seguros:**
```php
protected function applyFilters($query, array $filters): void
{
    foreach ($filters as $field => $value) {
        if ($this->isValidField($field)) {
            if (is_array($value) && isset($value[\'operator\'])) {
                $query->where($field, $value[\'operator\'], $value[\'value\']);
            } else {
                $query->where($field, $value);
            }
        }
    }
}
```

### **Estatísticas e Agregações:**
```php
public function getStatsByTenant(int $tenantId): array
{
    $baseQuery = $this->model->where(\'tenant_id\', $tenantId);

    return [
        \'total\' => $baseQuery->count(),
        \'active\' => (clone $baseQuery)->where(\'active\', true)->count(),
        \'total_value\' => $baseQuery->sum(\'total_value\'),
        \'average_value\' => $baseQuery->avg(\'total_value\')
    ];
}
```

## Boas Práticas

### **1. Performance**
- Use eager loading para relacionamentos (`with()`)
- Implemente paginação para grandes datasets
- Use índices adequados nas consultas
- Evite N+1 queries

### **2. Segurança**
- Valide campos antes de aplicar filtros
- Use parameterized queries sempre
- Evite raw queries desnecessárias
- Valide dados de entrada

### **3. Manutenibilidade**
- Documente métodos específicos
- Use nomes descritivos para métodos
- Mantenha consistência entre repositories similares
- Documente relacionamentos importantes

### **4. Testabilidade**
- Repositories devem ser testáveis unitariamente
- Use injeção de dependência quando necessário
- Evite lógica de negócio complexa no repository
- Foque em acesso a dados

### **5. Consistência**
- Siga o padrão estabelecido para cada nível
- Use convenções de nomenclatura consistentes
- Mantenha estrutura similar entre repositories
- Documente exceções quando necessário';
    }

}
