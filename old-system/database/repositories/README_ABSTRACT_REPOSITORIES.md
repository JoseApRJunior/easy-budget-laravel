# Classes Abstratas para Repositórios

Este documento explica como usar as classes abstratas base para repositórios no projeto Easy-Budget.

## Estrutura Criada

### 1. `AbstractRepository.php`
**Para repositórios COM tenant** - Implementa `RepositoryInterface`

### 2. `AbstractNoTenantRepository.php` 
**Para repositórios SEM tenant** - Implementa `RepositoryNoTenantInterface`

---

## Como Usar

### Para Entidades COM tenant_id

```php
<?php

namespace app\database\repositories;

use app\database\entitiesORM\MinhaEntidadeComTenant;
use app\database\repositories\AbstractRepository;

/**
 * @template T of MinhaEntidadeComTenant
 * @extends AbstractRepository<T>
 */
class MinhaEntidadeComTenantRepository extends AbstractRepository
{
    // Métodos específicos da entidade (opcionais)
    
    /**
     * Busca entidades por campo específico e tenant.
     */
    public function findByNomeAndTenantId(string $nome, int $tenant_id): array
    {
        return $this->findBy(['nome' => $nome, 'tenantId' => $tenant_id]);
    }
}
```

### Para Entidades SEM tenant_id

```php
<?php

namespace app\database\repositories;

use app\database\entitiesORM\MinhaEntidadeSemTenant;
use app\database\repositories\AbstractNoTenantRepository;

/**
 * @template T of MinhaEntidadeSemTenant
 * @extends AbstractNoTenantRepository<T>
 */
class MinhaEntidadeSemTenantRepository extends AbstractNoTenantRepository
{
    // Métodos específicos da entidade (opcionais)
    
    /**
     * Busca entidades ativas por nome.
     */
    public function findActiveByName(string $nome): array
    {
        return $this->findActive(['nome' => 'ASC']);
    }
}
```

---

## Métodos Disponíveis

### AbstractRepository (COM tenant)

#### Métodos Obrigatórios da Interface:
- `findByIdAndTenantId(int $id, int $tenant_id): ?EntityORMInterface`
- `findAllByTenantId(int $tenant_id, array $criteria = []): array`
- `save(EntityORMInterface $entity, int $tenant_id): EntityORMInterface`
- `deleteByIdAndTenantId(int $id, int $tenant_id): bool`

#### Métodos Auxiliares Protegidos:
- `findBySlugAndTenantId(string $slug, int $tenant_id): ?EntityORMInterface`
- `findActiveByTenantId(int $tenant_id): array`
- `countByTenantId(int $tenant_id, array $criteria = []): int`
- `existsByTenantId(int $id, int $tenant_id): bool`
- `validateTenantOwnership(EntityORMInterface $entity, int $tenant_id): void`
- `isSlugUniqueInTenant(string $slug, int $tenant_id, ?int $excludeId = null): bool`

### AbstractNoTenantRepository (SEM tenant)

#### Métodos Obrigatórios da Interface:
- `findById(int $id): ?EntityORMInterface`
- `findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array`
- `findAll(array $criteria = []): array`
- `save(EntityORMInterface $entity): EntityORMInterface|false`
- `delete(int $id): bool`

#### Métodos Auxiliares:
- `count(array $criteria = []): int` (público)
- `findBySlug(string $slug): ?EntityORMInterface` (protegido)
- `findActive(): array` (protegido)
- `exists(int $id): bool` (protegido)

---

## Vantagens

### 🔒 **Segurança Automática**
- **AbstractRepository**: Logs automáticos de tentativas cross-tenant
- **AbstractNoTenantRepository**: Tratamento seguro de erros

### 🚀 **Produtividade**
- Implementação automática de todos os métodos obrigatórios
- Métodos auxiliares pré-construídos
- Redução de código duplicado

### 🛠️ **Flexibilidade**
- Métodos podem ser sobrescritos quando necessário
- Métodos auxiliares protegidos para extensão
- Compatível com padrões existentes do projeto

### 📝 **Consistência**
- Padrões de erro unificados
- Logs de segurança padronizados
- Tratamento de exceções consistente

---

## Exemplos Práticos

### Exemplo 1: Repository Simples COM Tenant
```php
// Apenas estende AbstractRepository - todos os métodos já funcionam!
class BudgetRepository extends AbstractRepository
{
    // Pronto para usar!
}

// Uso:
$budget = $budgetRepository->findByIdAndTenantId(123, $tenant_id);
$budgets = $budgetRepository->findAllByTenantId($tenant_id);
```

### Exemplo 2: Repository Simples SEM Tenant
```php
// Apenas estende AbstractNoTenantRepository - todos os métodos já funcionam!
class CategoryRepository extends AbstractNoTenantRepository
{
    // Pronto para usar!
}

// Uso:
$category = $categoryRepository->findById(123);
$categories = $categoryRepository->findAll();
```

### Exemplo 3: Repository com Métodos Específicos
```php
class ActivityRepository extends AbstractRepository
{
    /**
     * Busca atividades por usuário e tenant.
     */
    public function findByUserIdAndTenantId(int $userId, int $tenant_id): array
    {
        return $this->findBy(['userId' => $userId, 'tenantId' => $tenant_id]);
    }
    
    /**
     * Busca atividades por tipo de ação.
     */
    public function findByActionTypeAndTenantId(string $actionType, int $tenant_id): array
    {
        return $this->findBy(['actionType' => $actionType, 'tenantId' => $tenant_id]);
    }
}
```

---

## Migração de Repositórios Existentes

### Para Repositórios COM Tenant:
1. Mude `extends EntityRepository` para `extends AbstractRepository`
2. Remove implementações manuais dos métodos obrigatórios
3. Mantenha apenas métodos específicos da entidade

### Para Repositórios SEM Tenant:
1. Mude `extends EntityRepository` para `extends AbstractNoTenantRepository`
2. Remove implementações manuais dos métodos obrigatórios
3. Mantenha apenas métodos específicos da entidade

---

## Conformidade com Padrões do Projeto

✅ **Interfaces**: Implementa completamente `RepositoryInterface` e `RepositoryNoTenantInterface`
✅ **Segurança**: Validação automática cross-tenant e logs de segurança
✅ **Padrões**: Segue as regras definidas nas memórias do projeto
✅ **Comentários**: Código completamente documentado em português
✅ **Nomenclatura**: Usa 'tenantId' padronizado conforme memórias
✅ **Tratamento de Erro**: RuntimeException com mensagens em português
✅ **Flexibilidade**: Permite sobrescrita quando necessário