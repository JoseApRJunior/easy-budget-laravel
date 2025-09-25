<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

/**
 * Repositório base com implementação padrão.
 * 
 * Fornece implementação base para operações CRUD com suporte a multi-tenancy,
 * baseado no padrão do sistema antigo adaptado para Eloquent ORM.
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;
    protected Builder $query;
    protected bool $resetAfterOperation = true;

    /**
     * Construtor do repositório.
     */
    public function __construct()
    {
        $this->model = $this->makeModel();
        $this->reset();
    }

    /**
     * Cria uma nova instância do modelo.
     */
    abstract protected function makeModel(): Model;

    /**
     * Obtém o nome da classe do modelo.
     */
    abstract protected function getModelClass(): string;

    /**
     * Encontra um registro por ID.
     */
    public function find(int $id): ?Model
    {
        $result = $this->query->find($id);
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Encontra um registro por ID ou falha.
     */
    public function findOrFail(int $id): Model
    {
        $result = $this->query->findOrFail($id);
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Encontra registros por critérios.
     */
    public function findBy(array $criteria): Collection
    {
        $this->applyCriteria($criteria);
        $result = $this->query->get();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Encontra um registro por critérios.
     */
    public function findOneBy(array $criteria): ?Model
    {
        $this->applyCriteria($criteria);
        $result = $this->query->first();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Obtém todos os registros.
     */
    public function all(): Collection
    {
        $result = $this->query->get();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Obtém registros paginados.
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $result = $this->query->paginate($perPage, $columns);
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Cria um novo registro.
     */
    public function create(array $data): Model
    {
        $this->validateTenantData($data);
        return $this->model->create($data);
    }

    /**
     * Atualiza um registro.
     */
    public function update(Model $model, array $data): Model
    {
        $this->validateTenantAccess($model);
        $this->validateTenantData($data);
        
        $model->update($data);
        return $model->fresh();
    }

    /**
     * Salva um registro (create ou update).
     */
    public function save(Model $model): Model
    {
        if ($model->exists) {
            $this->validateTenantAccess($model);
        } else {
            $this->validateTenantData($model->toArray());
        }
        
        $model->save();
        return $model;
    }

    /**
     * Exclui um registro.
     */
    public function delete(Model $model): bool
    {
        $this->validateTenantAccess($model);
        return $model->delete();
    }

    /**
     * Exclui um registro por ID.
     */
    public function deleteById(int $id): bool
    {
        $model = $this->findOrFail($id);
        return $this->delete($model);
    }

    /**
     * Conta registros por critérios.
     */
    public function count(array $criteria = []): int
    {
        if (!empty($criteria)) {
            $this->applyCriteria($criteria);
        }
        
        $result = $this->query->count();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Verifica se existe registro com critérios.
     */
    public function exists(array $criteria): bool
    {
        $this->applyCriteria($criteria);
        $result = $this->query->exists();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Obtém o primeiro registro ou cria um novo.
     */
    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        $data = array_merge($attributes, $values);
        $this->validateTenantData($data);
        
        return $this->model->firstOrCreate($attributes, $values);
    }

    /**
     * Atualiza ou cria um registro.
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $data = array_merge($attributes, $values);
        $this->validateTenantData($data);
        
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Aplica filtros de busca.
     */
    public function search(string $term, array $fields = []): Collection
    {
        if (empty($fields)) {
            throw new InvalidArgumentException('Campos de busca devem ser especificados');
        }

        $this->query->where(function (Builder $query) use ($term, $fields) {
            foreach ($fields as $field) {
                $query->orWhere($field, 'LIKE', "%{$term}%");
            }
        });

        $result = $this->query->get();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Obtém registros com relacionamentos.
     */
    public function with(array $relations): self
    {
        $this->query->with($relations);
        return $this;
    }

    /**
     * Aplica ordenação.
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Aplica filtros WHERE.
     */
    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $this->query->where($column, $operator);
        } else {
            $this->query->where($column, $operator, $value);
        }
        return $this;
    }

    /**
     * Aplica filtros WHERE IN.
     */
    public function whereIn(string $column, array $values): self
    {
        $this->query->whereIn($column, $values);
        return $this;
    }

    /**
     * Aplica filtros WHERE NOT IN.
     */
    public function whereNotIn(string $column, array $values): self
    {
        $this->query->whereNotIn($column, $values);
        return $this;
    }

    /**
     * Aplica filtros WHERE BETWEEN.
     */
    public function whereBetween(string $column, array $values): self
    {
        $this->query->whereBetween($column, $values);
        return $this;
    }

    /**
     * Aplica filtros WHERE NULL.
     */
    public function whereNull(string $column): self
    {
        $this->query->whereNull($column);
        return $this;
    }

    /**
     * Aplica filtros WHERE NOT NULL.
     */
    public function whereNotNull(string $column): self
    {
        $this->query->whereNotNull($column);
        return $this;
    }

    /**
     * Limita o número de resultados.
     */
    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * Pula um número de registros.
     */
    public function skip(int $offset): self
    {
        $this->query->skip($offset);
        return $this;
    }

    /**
     * Executa a query e retorna os resultados.
     */
    public function get(): Collection
    {
        $result = $this->query->get();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Executa a query e retorna o primeiro resultado.
     */
    public function first(): ?Model
    {
        $result = $this->query->first();
        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Reseta os filtros aplicados.
     */
    public function reset(): self
    {
        $this->query = $this->model->newQuery();
        return $this;
    }

    /**
     * Aplica critérios de busca.
     */
    protected function applyCriteria(array $criteria): void
    {
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $this->query->whereIn($field, $value);
            } else {
                $this->query->where($field, $value);
            }
        }
    }

    /**
     * Valida acesso do tenant ao modelo.
     */
    protected function validateTenantAccess(Model $model): void
    {
        if (!$this->hasTenantScope($model)) {
            return;
        }

        $currentTenantId = $this->getCurrentTenantId();
        $modelTenantId = $model->getAttribute('tenant_id');

        if ($currentTenantId !== $modelTenantId) {
            throw new InvalidArgumentException('Acesso negado: recurso não pertence ao tenant atual');
        }
    }

    /**
     * Valida dados do tenant.
     */
    protected function validateTenantData(array $data): void
    {
        if (!$this->modelHasTenantScope()) {
            return;
        }

        $currentTenantId = $this->getCurrentTenantId();
        
        if (isset($data['tenant_id']) && $data['tenant_id'] !== $currentTenantId) {
            throw new InvalidArgumentException('Tenant ID nos dados não corresponde ao tenant atual');
        }
    }

    /**
     * Verifica se o modelo tem escopo de tenant.
     */
    protected function hasTenantScope(Model $model): bool
    {
        return in_array('App\Traits\TenantScoped', class_uses_recursive($model));
    }

    /**
     * Verifica se o modelo da classe tem escopo de tenant.
     */
    protected function modelHasTenantScope(): bool
    {
        return $this->hasTenantScope($this->model);
    }

    /**
     * Obtém o ID do tenant atual.
     */
    protected function getCurrentTenantId(): ?int
    {
        return auth()->user()?->tenant_id ?? session('tenant_id');
    }

    /**
     * Reseta a query se necessário.
     */
    protected function resetIfNeeded(): void
    {
        if ($this->resetAfterOperation) {
            $this->reset();
        }
    }

    /**
     * Desabilita o reset automático após operações.
     */
    public function withoutAutoReset(): self
    {
        $this->resetAfterOperation = false;
        return $this;
    }

    /**
     * Habilita o reset automático após operações.
     */
    public function withAutoReset(): self
    {
        $this->resetAfterOperation = true;
        return $this;
    }
}