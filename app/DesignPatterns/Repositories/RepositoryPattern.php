<?php

declare(strict_types=1);

namespace App\DesignPatterns\Repositories;

/**
 * Padrão Unificado para Repositories no Easy Budget Laravel
 *
 * Define convenções consistentes para desenvolvimento de repositories,
 * garantindo uniformidade, manutenibilidade e reutilização de código.
 */
class RepositoryPattern
{
    /**
     * PADRÃO UNIFICADO PARA REPOSITORIES
     *
     * Baseado na análise dos repositories existentes, definimos 3 níveis:
     */

    /**
     * NÍVEL 1 - Repository Básico (CRUD Simples)
     * Para repositories com operações básicas sem lógica complexa
     *
     * @example PlanRepository, CategoryRepository
     */
    public function basicRepository(): string
    {
        return '
class BasicRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    abstract protected function makeModel(): Model;

    // Implementação básica dos métodos CRUD
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Model
    {
        $model = $this->find($id);
        if (!$model) return null;

        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = $this->find($id);
        return $model ? $model->delete() : false;
    }

    // Métodos específicos básicos
    public function findActive(): Collection
    {
        return $this->model->where(\'active\', true)->get();
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->model->where(\'slug\', $slug)->first();
    }
}';
    }

    /**
     * NÍVEL 2 - Repository Intermediário (Com Filtros)
     * Para repositories com filtros e operações avançadas
     *
     * @example CustomerRepository, ProductRepository
     */
    public function intermediateRepository(): string
    {
        return '
abstract class IntermediateRepository extends AbstractTenantRepository
{
    // Filtros suportados pelo repository
    protected function getSupportedFilters(): array
    {
        return [
            \'id\', \'name\', \'status\', \'active\',
            \'created_at\', \'updated_at\', \'tenant_id\'
        ];
    }

    // Operações específicas de negócio
    public function findActiveByTenant(int $tenantId): Collection
    {
        return $this->getAllByTenant(
            [\'active\' => true],
            [\'name\' => \'asc\']
        );
    }

    public function findByTenantAndStatus(int $tenantId, string $status): Collection
    {
        return $this->getAllByTenant(
            [\'status\' => $status],
            [\'created_at\' => \'desc\']
        );
    }

    public function countByTenantAndStatus(int $tenantId, string $status): int
    {
        return $this->countByTenant([\'status\' => $status]);
    }

    public function paginateActiveByTenant(int $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateByTenant($perPage, [\'active\' => true]);
    }

    // Validações de unicidade
    public function isUniqueInTenant(string $field, $value, ?int $excludeId = null): bool
    {
        return !$this->isUniqueInTenant($field, $value, $excludeId);
    }

    // Operações em lote
    public function bulkUpdateByTenant(int $tenantId, array $criteria, array $data): int
    {
        $query = $this->model->newQuery();

        // Aplica tenant automaticamente via Global Scope
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->update($data);
    }

    public function bulkDeleteByTenant(int $tenantId, array $criteria): int
    {
        $query = $this->model->newQuery();

        // Aplica tenant automaticamente via Global Scope
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->delete();
    }
}';
    }

    /**
     * NÍVEL 3 - Repository Avançado (Com Relacionamentos)
     * Para repositories com relacionamentos complexos e queries otimizadas
     *
     * @example BudgetRepository, InvoiceRepository
     */
    public function advancedRepository(): string
    {
        return '
abstract class AdvancedRepository extends AbstractTenantRepository
{
    protected function getSupportedFilters(): array
    {
        return [
            \'id\', \'name\', \'status\', \'type\', \'category_id\',
            \'created_at\', \'updated_at\', \'tenant_id\',
            \'customer_id\', \'total_value\', \'due_date\'
        ];
    }

    // Queries otimizadas com relacionamentos
    public function findWithRelations(int $id, array $relations = []): ?Model
    {
        $query = $this->model->with($relations);
        return $query->find($id);
    }

    public function getAllWithRelations(array $relations = [], array $criteria = []): Collection
    {
        $query = $this->model->with($relations);
        return $this->getAllByTenant($criteria, [\'created_at\' => \'desc\']);
    }

    public function paginateWithRelations(
        int $perPage = 15,
        array $relations = [],
        array $criteria = []
    ): LengthAwarePaginator {
        $query = $this->model->with($relations);
        return $this->paginateByTenant($perPage, $criteria);
    }

    // Queries específicas de negócio
    public function findByCustomerAndTenant(int $customerId, int $tenantId): Collection
    {
        return $this->getAllByTenant([
            \'customer_id\' => $customerId,
            \'tenant_id\' => $tenantId
        ], [\'created_at\' => \'desc\']);
    }

    public function findByDateRangeAndTenant(
        int $tenantId,
        string $startDate,
        string $endDate,
        array $additionalCriteria = []
    ): Collection {
        $criteria = array_merge([
            \'created_at\' => [
                \'operator\' => \'between\',
                \'value\' => [$startDate, $endDate]
            ]
        ], $additionalCriteria);

        return $this->getAllByTenant($criteria, [\'created_at\' => \'desc\']);
    }

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

    // Estatísticas avançadas
    public function getStatsByTenant(int $tenantId, array $criteria = []): array
    {
        $baseQuery = $this->model->newQuery();

        // Aplica filtros
        $this->applyFilters($baseQuery, $criteria);

        return [
            \'total\' => $baseQuery->count(),
            \'active\' => (clone $baseQuery)->where(\'active\', true)->count(),
            \'inactive\' => (clone $baseQuery)->where(\'active\', false)->count(),
            \'by_status\' => (clone $baseQuery)
                ->selectRaw(\'status, COUNT(*) as count\')
                ->groupBy(\'status\')
                ->pluck(\'count\', \'status\')
                ->toArray()
        ];
    }

    // Operações de agregação
    public function getSumByTenant(int $tenantId, string $field, array $criteria = []): float
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $criteria);

        return $query->sum($field);
    }

    public function getAverageByTenant(int $tenantId, string $field, array $criteria = []): float
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $criteria);

        return $query->avg($field);
    }

    // Operações específicas com relacionamentos
    public function findWithCustomerAndItems(int $id): ?Model
    {
        return $this->model->with([
            \'customer\',
            \'items\',
            \'items.product\'
        ])->find($id);
    }

    public function getRecentByTenant(int $tenantId, int $limit = 10): Collection
    {
        return $this->getAllByTenant(
            [],
            [\'created_at\' => \'desc\'],
            $limit
        );
    }
}';
    }

    /**
     * CONVENÇÕES PARA OPERAÇÕES DE BANCO
     */

    /**
     * Uso Correto das Operações de Banco
     */
    public function databaseConventions(): string
    {
        return '
// ✅ CORRETO - Operações de banco padronizadas

// 1. Sempre usar o modelo através do repository
public function find(int $id): ?Model
{
    return $this->model->find($id);
}

// 2. Tratamento consistente de não encontrado
public function find(int $id): ?Model
{
    $model = $this->model->find($id);
    if (!$model) {
        return null; // Retorna null ao invés de exception
    }
    return $model;
}

// 3. Queries otimizadas com eager loading
public function findWithRelations(int $id, array $relations = []): ?Model
{
    if (empty($relations)) {
        return $this->find($id);
    }

    return $this->model->with($relations)->find($id);
}

// 4. Filtros seguros e validados
protected function applyFilters($query, array $filters): void
{
    foreach ($filters as $field => $value) {
        if ($this->isValidField($field)) {
            if (is_array($value)) {
                // Suporte a operadores: [\'operator\' => \'>\', \'value\' => 100]
                if (isset($value[\'operator\'], $value[\'value\'])) {
                    $query->where($field, $value[\'operator\'], $value[\'value\']);
                } else {
                    $query->whereIn($field, $value);
                }
            } else {
                $query->where($field, $value);
            }
        }
    }
}

// 5. Ordenação segura
protected function applyOrderBy($query, array $orderBy): void
{
    $allowedFields = $this->getSortableFields();

    foreach ($orderBy as $field => $direction) {
        if (in_array($field, $allowedFields)) {
            $direction = strtolower($direction) === \'desc\' ? \'desc\' : \'asc\';
            $query->orderBy($field, $direction);
        }
    }
}

// ❌ INCORRETO - Não fazer isso

// 1. Não usar query builder diretamente no service
public function findByName(string $name): Collection
{
    return DB::table(\'users\')->where(\'name\', $name)->get(); // ❌ Errado
}

// 2. Não retornar exceptions para não encontrado
public function find(int $id): Model
{
    return $this->model->findOrFail($id); // ❌ Lança exception
}

// 3. Não fazer queries complexas no repository
public function getComplexReport(): array
{
    return DB::select("
        SELECT c.name, COUNT(b.id) as budget_count, SUM(b.total) as total
        FROM customers c
        LEFT JOIN budgets b ON c.id = b.customer_id
        GROUP BY c.id
    "); // ❌ Muito complexo para repository
}

// 4. Não misturar responsabilidades
public function sendNotification(int $id): bool
{
    $model = $this->find($id);
    // Lógica de notificação aqui... // ❌ Repository não deve enviar notificações
    return true;
}';
    }

    /**
     * EXEMPLOS PRÁTICOS DE IMPLEMENTAÇÃO
     */

    /**
     * Exemplo de Repository Nível 1 - Básico
     */
    public function basicRepositoryExample(): string
    {
        return '<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository básico para categorias - Apenas operações CRUD
 */
class CategoryRepository implements BaseRepositoryInterface
{
    protected Category $model;

    public function __construct()
    {
        $this->model = new Category();
    }

    public function find(int $id): ?Category
    {
        return $this->model->find($id);
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function create(array $data): Category
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Category
    {
        $category = $this->find($id);
        if (!$category) return null;

        $category->update($data);
        return $category->fresh();
    }

    public function delete(int $id): bool
    {
        $category = $this->find($id);
        return $category ? $category->delete() : false;
    }

    // Métodos específicos básicos
    public function findActive(): Collection
    {
        return $this->model->where(\'active\', true)->get();
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->model->where(\'slug\', $slug)->first();
    }

    public function findOrderedByName(string $direction = \'asc\'): Collection
    {
        return $this->model->orderBy(\'name\', $direction)->get();
    }
}';
    }

    /**
     * Exemplo de Repository Nível 2 - Intermediário
     */
    public function intermediateRepositoryExample(): string
    {
        return '<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repository intermediário para produtos - Com filtros e operações avançadas
 */
class ProductRepository extends AbstractTenantRepository
{
    protected function makeModel(): Product
    {
        return new Product();
    }

    protected function getSupportedFilters(): array
    {
        return [
            \'id\', \'name\', \'sku\', \'price\', \'active\',
            \'category_id\', \'created_at\', \'updated_at\'
        ];
    }

    protected function getSortableFields(): array
    {
        return [
            \'id\', \'name\', \'sku\', \'price\',
            \'created_at\', \'updated_at\'
        ];
    }

    // Operações específicas de produto
    public function findActiveByTenant(int $tenantId): Collection
    {
        return $this->getAllByTenant(
            [\'active\' => true],
            [\'name\' => \'asc\']
        );
    }

    public function findByCategoryAndTenant(int $categoryId, int $tenantId): Collection
    {
        return $this->getAllByTenant([
            \'category_id\' => $categoryId,
            \'active\' => true
        ], [\'name\' => \'asc\']);
    }

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

    public function paginateActiveByTenant(int $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateByTenant($perPage, [\'active\' => true]);
    }

    public function countActiveByTenant(int $tenantId): int
    {
        return $this->countByTenant([\'active\' => true]);
    }

    public function findLowStockByTenant(int $tenantId, int $threshold = 10): Collection
    {
        return $this->getAllByTenant([
            \'active\' => true
        ])->filter(function ($product) use ($threshold) {
            return ($product->inventory?->quantity ?? 0) <= $threshold;
        });
    }

    public function bulkUpdatePricesByTenant(
        int $tenantId,
        array $productIds,
        float $priceIncrease
    ): int {
        return $this->bulkUpdateByTenant($tenantId, [
            \'id\' => [\'operator\' => \'in\', \'value\' => $productIds]
        ], [
            \'price\' => DB::raw("price * {$priceIncrease}")
        ]);
    }
}';
    }

    /**
     * Exemplo de Repository Nível 3 - Avançado
     */
    public function advancedRepositoryExample(): string
    {
        return '<?php

namespace App\Repositories;

use App\Models\Budget;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Repository avançado para orçamentos - Com relacionamentos e queries complexas
 */
class BudgetRepository extends AbstractTenantRepository
{
    protected function makeModel(): Budget
    {
        return new Budget();
    }

    protected function getSupportedFilters(): array
    {
        return [
            \'id\', \'code\', \'customer_id\', \'status\', \'total_value\',
            \'due_date\', \'created_at\', \'updated_at\', \'tenant_id\'
        ];
    }

    protected function getSortableFields(): array
    {
        return [
            \'id\', \'code\', \'total_value\', \'due_date\',
            \'created_at\', \'updated_at\'
        ];
    }

    // Queries otimizadas com relacionamentos
    public function findWithCustomerAndItems(int $id): ?Budget
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

    public function getAllWithCustomerByTenant(int $tenantId): Collection
    {
        return $this->model->with([
            \'customer:id,name\',
            \'customer.commonData:id,first_name,last_name\'
        ])->where(\'tenant_id\', $tenantId)->get();
    }

    public function paginateWithFullDetails(
        int $tenantId,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->model->with([
            \'customer:id,name\',
            \'items:id,budget_id,product_id,quantity,unit_price,total\',
            \'items.product:id,name\',
            \'statusInfo:id,name,color\'
        ])->where(\'tenant_id\', $tenantId)
          ->paginate($perPage);
    }

    // Queries específicas de negócio
    public function findByCustomerAndTenant(int $customerId, int $tenantId): Collection
    {
        return $this->getAllByTenant([
            \'customer_id\' => $customerId
        ], [\'created_at\' => \'desc\']);
    }

    public function findOverdueByTenant(int $tenantId): Collection
    {
        return $this->getAllByTenant([
            \'status\' => \'approved\',
            \'due_date\' => [
                \'operator\' => \'<\',
                \'value\' => now()->toDateString()
            ]
        ], [\'due_date\' => \'asc\']);
    }

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

    // Estatísticas avançadas
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
            \'by_month\' => (clone $baseQuery)
                ->selectRaw(\'MONTH(created_at) as month, COUNT(*) as count, SUM(total_value) as total\')
                ->groupBy(\'month\')
                ->orderBy(\'month\')
                ->pluck(\'total\', \'month\')
                ->toArray()
        ];
    }

    public function getTopCustomersByBudgetCount(int $tenantId, int $limit = 10): Collection
    {
        return $this->model->select(\'customer_id\', DB::raw(\'COUNT(*) as budget_count\'))
            ->with(\'customer:id,name\')
            ->where(\'tenant_id\', $tenantId)
            ->groupBy(\'customer_id\')
            ->orderByDesc(\'budget_count\')
            ->limit($limit)
            ->get();
    }

    public function getMonthlyRevenueByTenant(int $tenantId, int $year): Collection
    {
        return $this->model->select(
                DB::raw(\'MONTH(created_at) as month\'),
                DB::raw(\'SUM(total_value) as revenue\'),
                DB::raw(\'COUNT(*) as budget_count\')
            )
            ->where(\'tenant_id\', $tenantId)
            ->whereYear(\'created_at\', $year)
            ->where(\'status\', \'approved\')
            ->groupBy(\'month\')
            ->orderBy(\'month\')
            ->get();
    }

    // Operações de agregação
    public function getTotalValueByTenant(int $tenantId, array $criteria = []): float
    {
        return $this->getSumByTenant($tenantId, \'total_value\', $criteria);
    }

    public function getAverageValueByTenant(int $tenantId, array $criteria = []): float
    {
        return $this->getAverageByTenant($tenantId, \'total_value\', $criteria);
    }

    // Operações específicas com relacionamentos
    public function findWithItemsAndProducts(int $budgetId): ?Budget
    {
        return $this->model->with([
            \'items\' => function ($query) {
                $query->select(\'id\', \'budget_id\', \'product_id\', \'quantity\', \'unit_price\', \'total\');
            },
            \'items.product\' => function ($query) {
                $query->select(\'id\', \'name\', \'description\');
            }
        ])->find($budgetId);
    }

    public function getBudgetsWithPendingApproval(int $tenantId): Collection
    {
        return $this->model->with([
            \'customer:id,name\',
            \'items:id,budget_id,product_id,quantity,total\'
        ])
        ->where(\'tenant_id\', $tenantId)
        ->where(\'status\', \'pending\')
        ->orderBy(\'created_at\', \'asc\')
        ->get();
    }

    public function getRecentBudgetsByTenant(int $tenantId, int $days = 7): Collection
    {
        return $this->getAllByTenant([
            \'created_at\' => [
                \'operator\' => \'>\',
                \'value\' => now()->subDays($days)->toDateString()
            ]
        ], [\'created_at\' => \'desc\'], 50);
    }
}';
    }

    /**
     * GUIA DE IMPLEMENTAÇÃO
     */
    public function getImplementationGuide(): string
    {
        return '
## Guia de Implementação - Escolhendo o Nível Correto

### NÍVEL 1 - Repository Básico
✅ Quando usar:
- Entidades simples sem relacionamentos complexos
- Operações CRUD básicas suficientes
- Não há necessidade de filtros avançados
- Modelo global (não multi-tenant)

❌ Não usar quando:
- Relacionamentos importantes para o negócio
- Filtros complexos necessários
- Multi-tenant requerido
- Operações específicas necessárias

### NÍVEL 2 - Repository Intermediário
✅ Quando usar:
- Multi-tenant necessário
- Filtros e paginação importantes
- Operações específicas além do CRUD básico
- Validações de unicidade necessárias

❌ Não usar quando:
- Relacionamentos complexos são críticos
- Estatísticas avançadas necessárias
- Queries de agregação complexas
- Modelo global (não multi-tenant)

### NÍVEL 3 - Repository Avançado
✅ Quando usar:
- Relacionamentos complexos essenciais
- Estatísticas e relatórios necessários
- Queries de agregação importantes
- Performance crítica com grandes volumes

❌ Não usar quando:
- Operações simples são suficientes
- Relacionamentos não são importantes
- Projeto inicial sem necessidade de complexidade

## Benefícios do Padrão

✅ **Consistência**: Todos os repositories seguem convenções unificadas
✅ **Performance**: Queries otimizadas com eager loading
✅ **Manutenibilidade**: Estrutura clara e fácil de entender
✅ **Flexibilidade**: Diferentes níveis para diferentes necessidades
✅ **Testabilidade**: Fácil de mockar e testar
✅ **Escalabilidade**: Preparado para crescimento

## Estrutura Recomendada

```
app/Repositories/
├── Contracts/                           # Interfaces
│   ├── BaseRepositoryInterface.php     # Interface básica
│   └── TenantRepositoryInterface.php   # Interface multi-tenant
├── Abstracts/                          # Classes abstratas
│   ├── AbstractBaseRepository.php      # Base para todos
│   └── AbstractTenantRepository.php    # Base para multi-tenant
└── Domain/                             # Repositories concretos
    ├── BasicRepository.php            # Nível 1 - Básico
    ├── IntermediateRepository.php     # Nível 2 - Intermediário
    └── AdvancedRepository.php         # Nível 3 - Avançado
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
// Sempre usar métodos do AbstractTenantRepository
public function getAllByTenant(array $criteria = [], ?array $orderBy = null): Collection
public function paginateByTenant(int $perPage = 15, array $filters = []): LengthAwarePaginator
public function countByTenant(array $filters = []): int
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
public function findWithCustomerAndItems(int $id): ?Model
{
    return $this->model->with([
        \'customer\',
        \'items\',
        \'items.product\'
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
