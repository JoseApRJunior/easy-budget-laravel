# ANÁLISE DA ARQUITETURA DE REPOSITORIES - EASY BUDGET LARAVEL

## 📋 ANÁLISE EXECUTIVA

Esta análise detalha a arquitetura atual de repositories do sistema Easy Budget Laravel e propõe uma nova estrutura padronizada com interfaces base, tenant-aware e global.

## 🎯 OBJETIVOS DA ANÁLISE

1. **Analisar modelos existentes** para identificar padrões de tenant-scoping
2. **Classificar modelos** por categoria (tenant-scoped vs globais)
3. **Definir interfaces base** para padronização
4. **Propor estrutura de repositories** otimizada
5. **Criar documentação** da arquitetura proposta

---

## 📊 ANÁLISE DOS MODELOS EXISTENTES

### 🔍 Padrões Identificados

#### **Modelos Tenant-Scoped** (com `tenant_id`)

Estes modelos usam a trait `TenantScoped` e implementam business rules específicas:

| Modelo         | Tenant ID | Business Rules | Validações Customizadas                                              | Status         |
| -------------- | --------- | -------------- | -------------------------------------------------------------------- | -------------- |
| **User**       | ✅        | ✅             | `validateUniqueEmailInTenant()`                                      | ✅ Completo    |
| **Budget**     | ✅        | ✅             | `validateUniqueCodeInTenant()`, `validateTotalGreaterThanDiscount()` | ✅ Completo    |
| **Service**    | ✅        | ✅             | -                                                                    | ✅ Completo    |
| **Customer**   | ✅        | ✅             | -                                                                    | ✅ Completo    |
| **Provider**   | ✅        | ✅             | -                                                                    | 🔄 A confirmar |
| **Product**    | ✅        | ✅             | -                                                                    | 🔄 A confirmar |
| **Invoice**    | ✅        | ✅             | -                                                                    | 🔄 A confirmar |
| **Address**    | ✅        | ✅             | -                                                                    | 🔄 A confirmar |
| **Contact**    | ✅        | ✅             | -                                                                    | 🔄 A confirmar |
| **CommonData** | ✅        | ✅             | -                                                                    | 🔄 A confirmar |

#### **Modelos Globais** (sem `tenant_id`)

Estes modelos são compartilhados entre todos os tenants:

| Modelo            | Tenant ID       | Business Rules | Descrição                                | Status         |
| ----------------- | --------------- | -------------- | ---------------------------------------- | -------------- |
| **Tenant**        | ❌ (Modelo pai) | ✅             | Entidade raiz do sistema                 | ✅ Completo    |
| **Plan**          | ❌              | ✅             | Planos de assinatura                     | ✅ Completo    |
| **BudgetStatus**  | ❌              | ✅             | Status de orçamentos                     | ✅ Completo    |
| **ServiceStatus** | ❌              | ✅             | Status de serviços                       | ✅ Completo    |
| **Role**          | ❌              | ✅             | Perfis de usuário (RBAC)                 | ✅ Completo    |
| **Permission**    | ❌              | ✅             | Permissões (RBAC)                        | ✅ Completo    |
| **Category**      | ⚠️ Híbrido\*    | ✅             | Categorias (pode ter tenant_id opcional) | ⚠️ Especial    |
| **Profession**    | ❌              | ✅             | Profissões                               | 🔄 A confirmar |
| **Unit**          | ❌              | ✅             | Unidades de medida                       | 🔄 A confirmar |

> \*Category usa TenantScoped trait mas pode funcionar como híbrido

#### **Modelos Especiais**

-  **UserConfirmationToken**: Tenant-scoped, usado para confirmação de contas
-  **PlanSubscription**: Relaciona Tenant com Plan (híbrido)
-  **UserRole**: Pivot entre User e Role com tenant_id

---

## 🏗️ ARQUITETURA ATUAL

### Interfaces Existentes

#### **BaseRepositoryInterface**

```php
interface BaseRepositoryInterface
{
    public function getModelClass(): string;
    public function newModel(array $attributes = []): Model;
    public function newQuery(): mixed;
    public function count(): int;
    public function exists(): bool;
    public function truncate(): bool;
    public function first(): ?Model;
    public function last(): ?Model;
    public function transaction(callable $callback): mixed;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
    public function refresh(Model $entity): ?Model;
}
```

#### **RepositoryInterface** (Tenant-Aware)

```php
interface RepositoryInterface extends BaseRepositoryInterface
{
    // Busca por ID e tenant
    public function findByIdAndTenantId(int $id, int $tenantId): ?Model;
    public function findManyByIdsAndTenantId(array $id, int $tenantId): array;

    // Busca com critérios e tenant
    public function findByAndTenantId(array $criteria, int $tenantId, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
    public function findOneByAndTenantId(array $criteria, int $tenantId): ?Model;

    // CRUD com tenant
    public function save(Model $entity, int $tenantId): Model|false;
    public function update(Model $entity, int $tenantId): Model|false;
    public function deleteByIdAndTenantId(int $id, int $tenantId): bool;
    public function delete(Model $entity, int $tenantId): bool;

    // Operações em lote
    public function updateManyByTenantId(array $criteria, array $updates, int $tenantId): int;
    public function deleteManyByIdsAndTenantId(array $id, int $tenantId): int;

    // Paginação e contagem
    public function paginateByTenantId(int $tenantId, int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null): array;
    public function countByTenantId(int $tenantId, array $criteria = []): int;
    public function existsByTenantId(array $criteria, int $tenantId): bool;
}
```

#### **RepositoryNoTenantInterface** (Global)

```php
interface RepositoryNoTenantInterface extends BaseRepositoryInterface
{
    // CRUD global
    public function findById(int $id): ?Model;
    public function findManyByIds(array $id): array;
    public function save(Model $entity): Model|false;
    public function update(Model $entity): Model|false;
    public function deleteById(int $id): bool;
    public function delete(Model $entity): bool;

    // Busca com critérios
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
    public function findOneBy(array $criteria): ?Model;
    public function findBySlug(string $slug): ?Model;

    // Operações em lote
    public function updateMany(array $criteria, array $updates): int;
    public function deleteManyByIds(array $id): int;

    // Paginação global
    public function paginate(int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null): array;
    public function countBy(array $criteria = []): int;
    public function existsBy(array $criteria): bool;
}
```

### Repositórios Base Atuais

#### **AbstractRepository** (Tenant-Aware)

-  Implementa `RepositoryInterface`
-  Fornece métodos tenant-aware completos
-  Logging de operações
-  Tratamento de erros
-  Validação de ownership de tenant

#### **AbstractNoTenantRepository** (Global)

-  Implementa `RepositoryNoTenantInterface`
-  Fornece métodos globais sem tenant scoping
-  Logging de operações
-  Tratamento de erros

#### **BaseRepository** (Simples)

-  Implementa operações CRUD básicas
-  Suporte a multi-tenancy automático
-  Query builder fluente

---

## 🚀 PROPOSTA DE ARQUITETURA OTIMIZADA

### 🎯 Princípios da Nova Arquitetura

1. **Padronização Completa**: Todas as interfaces seguem o mesmo padrão
2. **Type Safety**: Tipos rigorosos em todas as interfaces
3. **Tenant Isolation**: Separação clara entre tenant-aware e global
4. **Extensibilidade**: Fácil adição de novos métodos específicos
5. **Performance**: Queries otimizadas para cada contexto
6. **Manutenibilidade**: Código auto-documentado e testável

### 📐 Interfaces Propostas

#### **1. BaseRepositoryInterface** (Melhorada)

```php
interface BaseRepositoryInterface
{
    // Metadata
    public function getModelClass(): string;
    public function getTable(): string;

    // Instance management
    public function newModel(array $attributes = []): Model;
    public function newQuery(): Builder;

    // Basic queries
    public function find(int|string $id): ?Model;
    public function findOrFail(int|string $id): Model;
    public function findMany(array $ids): Collection;

    // Transaction management
    public function transaction(callable $callback): mixed;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;

    // Utility
    public function exists(): bool;
    public function count(): int;
    public function truncate(): bool;
    public function refresh(Model $entity): ?Model;
}
```

#### **2. TenantRepositoryInterface**

```php
interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    // Tenant context
    public function setTenantId(int $tenantId): self;
    public function getTenantId(): ?int;

    // CRUD with tenant
    public function create(array $data): Model;
    public function update(Model $model, array $data): Model;
    public function delete(Model $model): bool;

    // Advanced queries with tenant
    public function findByIdAndTenant(int $id, int $tenantId): ?Model;
    public function findByCriteriaAndTenant(array $criteria, int $tenantId): Collection;
    public function findOneByCriteriaAndTenant(array $criteria, int $tenantId): ?Model;

    // Business-specific queries
    public function findBySlugAndTenant(string $slug, int $tenantId): ?Model;
    public function findByCodeAndTenant(string $code, int $tenantId): ?Model;

    // Bulk operations with tenant
    public function updateManyByTenant(array $criteria, array $updates, int $tenantId): int;
    public function deleteManyByTenant(array $criteria, int $tenantId): int;

    // Pagination with tenant
    public function paginateByTenant(int $tenantId, int $perPage = 15, array $criteria = []): LengthAwarePaginator;

    // Validation helpers
    public function validateTenantOwnership(Model $model, int $tenantId): bool;
    public function validateUniqueInTenant(string $field, mixed $value, int $tenantId, ?int $excludeId = null): bool;
}
```

#### **3. GlobalRepositoryInterface**

```php
interface GlobalRepositoryInterface extends BaseRepositoryInterface
{
    // CRUD global
    public function create(array $data): Model;
    public function update(Model $model, array $data): Model;
    public function delete(Model $model): bool;

    // Advanced queries global
    public function findByCriteria(array $criteria): Collection;
    public function findOneByCriteria(array $criteria): ?Model;
    public function findBySlug(string $slug): ?Model;

    // Bulk operations global
    public function updateMany(array $criteria, array $updates): int;
    public function deleteMany(array $criteria): int;

    // Pagination global
    public function paginate(int $perPage = 15, array $criteria = []): LengthAwarePaginator;

    // Validation helpers
    public function validateUnique(string $field, mixed $value, ?int $excludeId = null): bool;
}
```

### 🏭 Repositórios Base Propostos

#### **1. AbstractBaseRepository**

```php
abstract class AbstractBaseRepository implements BaseRepositoryInterface
{
    protected Model $model;
    protected Builder $query;
    protected bool $resetAfterOperation = true;

    public function __construct()
    {
        $this->model = $this->makeModel();
        $this->reset();
    }

    abstract protected function makeModel(): Model;
    abstract protected function getModelClass(): string;

    // Implementação dos métodos base
    // ... (métodos de query builder, transactions, etc.)
}
```

#### **2. AbstractTenantRepository**

```php
abstract class AbstractTenantRepository extends AbstractBaseRepository implements TenantRepositoryInterface
{
    protected ?int $tenantId = null;

    public function setTenantId(int $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId ?? auth()->user()?->tenant_id ?? session('tenant_id');
    }

    // Implementação dos métodos tenant-aware
    // ... (validação de ownership, queries com tenant_id, etc.)
}
```

#### **3. AbstractGlobalRepository**

```php
abstract class AbstractGlobalRepository extends AbstractBaseRepository implements GlobalRepositoryInterface
{
    // Implementação dos métodos globais
    // ... (sem tenant scoping, queries diretas, etc.)
}
```

---

## 📁 ESTRUTURA DE REPOSITORIES POR MODELO

### 🎯 Repositórios Tenant-Scoped

#### **UserRepository**

```php
class UserRepository extends AbstractTenantRepository
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    // Métodos específicos de User
    public function findByEmailAndTenant(string $email, int $tenantId): ?User
    {
        return $this->findOneByCriteriaAndTenant(['email' => $email], $tenantId);
    }

    public function findActiveByTenant(int $tenantId): Collection
    {
        return $this->findByCriteriaAndTenant(['is_active' => true], $tenantId);
    }

    public function validateUniqueEmailInTenant(string $email, int $tenantId, ?int $excludeId = null): bool
    {
        return $this->validateUniqueInTenant('email', $email, $tenantId, $excludeId);
    }
}
```

#### **BudgetRepository**

```php
class BudgetRepository extends AbstractTenantRepository
{
    protected function getModelClass(): string
    {
        return Budget::class;
    }

    // Métodos específicos de Budget
    public function findByCodeAndTenant(string $code, int $tenantId): ?Budget
    {
        return $this->findByCodeAndTenant($code, $tenantId);
    }

    public function findByCustomerAndTenant(int $customerId, int $tenantId): Collection
    {
        return $this->findByCriteriaAndTenant(['customer_id' => $customerId], $tenantId);
    }

    public function findByStatusAndTenant(int $statusId, int $tenantId): Collection
    {
        return $this->findByCriteriaAndTenant(['budget_statuses_id' => $statusId], $tenantId);
    }

    public function validateUniqueCodeInTenant(string $code, int $tenantId, ?int $excludeId = null): bool
    {
        return $this->validateUniqueInTenant('code', $code, $tenantId, $excludeId);
    }
}
```

#### **ServiceRepository**

```php
class ServiceRepository extends AbstractTenantRepository
{
    protected function getModelClass(): string
    {
        return Service::class;
    }

    // Métodos específicos de Service
    public function findByBudgetAndTenant(int $budgetId, int $tenantId): Collection
    {
        return $this->findByCriteriaAndTenant(['budget_id' => $budgetId], $tenantId);
    }

    public function findByCategoryAndTenant(int $categoryId, int $tenantId): Collection
    {
        return $this->findByCriteriaAndTenant(['category_id' => $categoryId], $tenantId);
    }

    public function findByStatusAndTenant(int $statusId, int $tenantId): Collection
    {
        return $this->findByCriteriaAndTenant(['service_statuses_id' => $statusId], $tenantId);
    }
}
```

### 🌍 Repositórios Globais

#### **PlanRepository**

```php
class PlanRepository extends AbstractGlobalRepository
{
    protected function getModelClass(): string
    {
        return Plan::class;
    }

    // Métodos específicos de Plan
    public function findActive(): Collection
    {
        return $this->findByCriteria(['status' => true]);
    }

    public function findBySlug(string $slug): ?Plan
    {
        return $this->findBySlug($slug);
    }

    public function findOrderedByPrice(string $direction = 'asc'): Collection
    {
        return $this->newQuery()->orderBy('price', $direction)->get();
    }
}
```

#### **BudgetStatusRepository**

```php
class BudgetStatusRepository extends AbstractGlobalRepository
{
    protected function getModelClass(): string
    {
        return BudgetStatus::class;
    }

    // Métodos específicos de BudgetStatus
    public function findActive(): Collection
    {
        return $this->findByCriteria(['is_active' => true]);
    }

    public function findOrderedByIndex(): Collection
    {
        return $this->newQuery()->orderBy('order_index')->get();
    }

    public function findBySlug(string $slug): ?BudgetStatus
    {
        return $this->findBySlug($slug);
    }
}
```

#### **RoleRepository**

```php
class RoleRepository extends AbstractGlobalRepository
{
    protected function getModelClass(): string
    {
        return Role::class;
    }

    // Métodos específicos de Role
    public function findByName(string $name): ?Role
    {
        return $this->findOneByCriteria(['name' => $name]);
    }

    public function findWithPermissions(): Collection
    {
        return $this->newQuery()->with('permissions')->get();
    }

    public function findUsersByRoleAndTenant(int $roleId, int $tenantId): Collection
    {
        $role = $this->find($roleId);
        return $role?->usersForTenant($tenantId) ?? collect();
    }
}
```

### 🔄 Repositórios Híbridos (Category)

#### **CategoryRepository**

```php
class CategoryRepository extends AbstractTenantRepository
{
    protected function getModelClass(): string
    {
        return Category::class;
    }

    // Métodos específicos de Category
    public function findActiveByTenant(int $tenantId): Collection
    {
        return $this->findByCriteriaAndTenant(['status' => 'active'], $tenantId);
    }

    public function findBySlugAndTenant(string $slug, int $tenantId): ?Category
    {
        return $this->findBySlugAndTenant($slug, $tenantId);
    }

    public function findWithServicesByTenant(int $tenantId): Collection
    {
        return $this->findByCriteriaAndTenant([], $tenantId)
            ->load('services');
    }
}
```

---

## 📋 PADRÕES DE NOMENCLATURA

### Convenções para Repositórios

| Tipo              | Padrão                       | Exemplo                              |
| ----------------- | ---------------------------- | ------------------------------------ |
| **Tenant-Scoped** | `{Model}Repository`          | `UserRepository`, `BudgetRepository` |
| **Global**        | `{Model}Repository`          | `PlanRepository`, `RoleRepository`   |
| **Interface**     | `{Model}RepositoryInterface` | `UserRepositoryInterface`            |
| **Service**       | `{Model}Service`             | `UserService`, `BudgetService`       |

### Convenções para Métodos

| Operação              | Padrão                                                                            | Exemplo                                                    |
| --------------------- | --------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| **Buscar por ID**     | `findById[AndTenant](int $id, ?int $tenantId)`                                    | `findByIdAndTenant(1, 123)`                                |
| **Buscar por slug**   | `findBySlug[AndTenant](string $slug, ?int $tenantId)`                             | `findBySlugAndTenant('slug', 123)`                         |
| **Buscar por código** | `findByCode[AndTenant](string $code, ?int $tenantId)`                             | `findByCodeAndTenant('BUD001', 123)`                       |
| **Listar ativos**     | `findActive[ByTenant](?int $tenantId)`                                            | `findActiveByTenant(123)`                                  |
| **Validação única**   | `validateUnique[InTenant](string $field, $value, int $tenantId, ?int $excludeId)` | `validateUniqueInTenant('email', 'test@test.com', 123, 1)` |

---

## 🧪 GUIA DE IMPLEMENTAÇÃO

### Passo 1: Criar Interfaces Específicas

```php
interface UserRepositoryInterface extends TenantRepositoryInterface
{
    public function findByEmailAndTenant(string $email, int $tenantId): ?User;
    public function findActiveByTenant(int $tenantId): Collection;
    public function validateUniqueEmailInTenant(string $email, int $tenantId, ?int $excludeId = null): bool;
}

interface BudgetRepositoryInterface extends TenantRepositoryInterface
{
    public function findByCodeAndTenant(string $code, int $tenantId): ?Budget;
    public function findByCustomerAndTenant(int $customerId, int $tenantId): Collection;
    public function findByStatusAndTenant(int $statusId, int $tenantId): Collection;
    public function validateUniqueCodeInTenant(string $code, int $tenantId, ?int $excludeId = null): bool;
}
```

### Passo 2: Implementar Repositórios

```php
class UserRepository extends AbstractTenantRepository implements UserRepositoryInterface
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    public function findByEmailAndTenant(string $email, int $tenantId): ?User
    {
        return $this->findOneByCriteriaAndTenant(['email' => $email], $tenantId);
    }

    public function findActiveByTenant(int $tenantId): Collection
    {
        return $this->findByCriteriaAndTenant(['is_active' => true], $tenantId);
    }

    public function validateUniqueEmailInTenant(string $email, int $tenantId, ?int $excludeId = null): bool
    {
        return $this->validateUniqueInTenant('email', $email, $tenantId, $excludeId);
    }
}
```

### Passo 3: Atualizar Services

```php
class UserService extends BaseTenantService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function getByEmailAndTenant(string $email, int $tenantId): ServiceResult
    {
        $user = $this->userRepository->findByEmailAndTenant($email, $tenantId);

        if (!$user) {
            return $this->error(OperationStatus::NOT_FOUND, 'Usuário não encontrado.');
        }

        return $this->success($user, 'Usuário encontrado com sucesso.');
    }
}
```

---

## ✅ BENEFÍCIOS DA NOVA ARQUITETURA

### 🎯 **Padronização**

-  Interfaces consistentes em todos os repositórios
-  Nomenclatura uniforme para métodos
-  Padrões de validação centralizados

### 🚀 **Performance**

-  Queries otimizadas para cada contexto
-  Lazy loading inteligente
-  Cache de metadados

### 🔒 **Segurança**

-  Validação automática de tenant ownership
-  Prevenção de data leaks entre tenants
-  Type safety em todas as operações

### 🧪 **Testabilidade**

-  Interfaces mockáveis
-  Separação clara de responsabilidades
-  Testes unitários mais fáceis

### 🔧 **Manutenibilidade**

-  Código auto-documentado
-  Refatoração mais segura
-  Adição de novos recursos simplificada

---

## 📅 PRÓXIMOS PASSOS

### Fase 1: Foundation (1-2 semanas)

1. ✅ Criar interfaces base propostas
2. ✅ Implementar repositórios base abstratos
3. ✅ Migrar 3 repositórios críticos (User, Budget, Plan)

### Fase 2: Expansion (2-3 semanas)

1. 🔄 Migrar repositórios restantes
2. 🔄 Atualizar services para usar novas interfaces
3. 🔄 Implementar testes automatizados

### Fase 3: Optimization (1-2 semanas)

1. 🔄 Adicionar cache onde apropriado
2. 🔄 Otimizar queries N+1
3. 🔄 Implementar auditoria de operações

### Fase 4: Documentation (1 semana)

1. 🔄 Documentar padrões de uso
2. 🔄 Criar guias de migração
3. 🔄 Treinar equipe de desenvolvimento

---

## 🎉 CONCLUSÃO

A nova arquitetura proposta oferece:

-  **🏗️ Base sólida** para crescimento futuro
-  **⚡ Performance superior** com queries otimizadas
-  **🔒 Segurança robusta** com validações automáticas
-  **🧪 Facilidade de teste** com interfaces bem definidas
-  **📖 Código limpo** e auto-documentado

Esta arquitetura posiciona o sistema Easy Budget Laravel para escalar de forma sustentável, mantendo a qualidade e a segurança dos dados multi-tenant.

---

_Documento criado em: 27 de Setembro de 2025_
_Versão: 1.0 - Proposta Inicial_
