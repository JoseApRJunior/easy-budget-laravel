---
name: service-layer-standard
description: Garante que Services do Easy Budget sigam o padrão com ServiceResult e separação de camadas.
---

# Padrão de Service Layer do Easy Budget

Esta skill define o padrão para criação e manutenção de Services no sistema Easy Budget. Os Services são organizados em camadas de responsabilidade: Domain, Application e Infrastructure.

## Estrutura de Camadas

```
app/Services/
├── Domain/          # Lógica de negócio específica da entidade
├── Application/     # Orquestração e workflows
├── Infrastructure/  # Integrações externas (e-mail, cache, arquivos)
└── Core/            # Abstrações e contratos compartilhados
```

## Padrão de Service Domain

```php
<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;

abstract class BaseTenantService
{
    protected int $tenantId;
    protected BaseRepositoryInterface $repository;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->tenantId = tenant('id');
    }

    /**
     * Lista registros filtrados por tenant.
     */
    public function list(array $filters = []): ServiceResult
    {
        try {
            $items = $this->repository->getAllByTenantId(
                $this->tenantId,
                $this->buildFilters($filters)
            );
            return $this->success($items, 'Listagem obtida com sucesso.');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Cria um novo registro.
     */
    public function create(array $data): ServiceResult
    {
        try {
            $data['tenant_id'] = $this->tenantId;

            $item = $this->repository->create($data);
            return $this->success($item, 'Registro criado com sucesso.');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Atualiza um registro existente.
     */
    public function update(int $id, array $data): ServiceResult
    {
        try {
            $item = $this->repository->findByIdAndTenantId($id, $this->tenantId);
            if (!$item) {
                return $this->error('Registro não encontrado.');
            }

            $item = $this->repository->update($item, $data);
            return $this->success($item, 'Registro atualizado com sucesso.');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Exclui um registro (soft delete).
     */
    public function delete(int $id): ServiceResult
    {
        try {
            $item = $this->repository->findByIdAndTenantId($id, $this->tenantId);
            if (!$item) {
                return $this->error('Registro não encontrado.');
            }

            $this->repository->delete($item);
            return $this->success(null, 'Registro excluído com sucesso.');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Constrói filtros normalizados.
     */
    protected function buildFilters(array $filters): array
    {
        // Implementação específica
        return $filters;
    }

    /**
     * Helpers para ServiceResult.
     */
    protected function success(mixed $data = null, string $message = ''): ServiceResult
    {
        return ServiceResult::success($data, $message);
    }

    protected function error(string $message): ServiceResult
    {
        return ServiceResult::error($message);
    }
}
```

## Regras de ServiceResult

1. **Sempre use `ServiceResult`** pararetornos de operações que podem falhar
2. **Mensagens claras**: Use mensagens que o usuário final pode entender
3. **Dados estruturados**: Retorne os dados necessários para a view/controller
4. **Logging**: Considere registrar erros para debugging

## Exemplo de Service Real

```php
<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Repositories\ProductRepository;
use App\Services\Core\Abstracts\AbstractBaseService;

class ProductService extends AbstractBaseService
{
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Lista produtos ativos por categoria.
     */
    public function listByCategory(int $categoryId, array $filters = []): ServiceResult
    {
        $filters['category_id'] = $categoryId;
        $filters['active'] = true;

        return $this->list($filters);
    }

    /**
     * Ativa/desativa um produto.
     */
    public function toggleStatus(int $id): ServiceResult
    {
        $product = $this->repository->findByIdAndTenantId($id, $this->tenantId);
        if (!$product) {
            return $this->error('Produto não encontrado.');
        }

        $newStatus = !$product->active;
        $this->repository->update($product, ['active' => $newStatus]);

        return $this->success(
            ['active' => $newStatus],
            $newStatus ? 'Produto ativado.' : 'Produto desativado.'
        );
    }
}
```

## Quando Criar um Service

- Quando há lógica de negócio que não cabe em um Repository
- Quando há validações de domínio específicas
- Quando há operações que envolvem múltiplos modelos
- Quando há necessidade de logging ou auditoria específica

## Quando NÃO Criar um Service

- Operações simples de CRUD que o Repository já resolve
- Lógica que é puramente de apresentação (Controller/View)
- Operações que só são usadas em uma Action
