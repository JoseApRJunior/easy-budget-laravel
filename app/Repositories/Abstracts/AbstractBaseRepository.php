<?php

declare(strict_types=1);

namespace App\Repositories\Abstracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Classe abstrata base para implementação do BaseRepositoryInterface.
 *
 * Esta classe fornece implementação padrão para as operações básicas de CRUD,
 * servindo como ponto de partida para repositórios específicos. Implementa
 * o contrato BaseRepositoryInterface garantindo consistência em toda aplicação.
 *
 *
 * @example Implementação de um repositório específico:
 * ```php
 * class UserRepository extends AbstractBaseRepository
 * {
 *     protected function getModelClass(): string
 *     {
 *         return User::class;
 *     }
 * }
 * ```
 */
abstract class AbstractBaseRepository implements BaseRepositoryInterface
{
    /**
     * Classe do modelo Eloquent que este repositório gerencia.
     *
     * @return string Nome completo da classe do modelo.
     */
    abstract protected function getModelClass(): string;

    /**
     * Retorna uma instância do modelo gerenciado por este repositório.
     *
     * @return Model Instância do modelo.
     */
    protected function getModel(): Model
    {
        $modelClass = $this->getModelClass();

        if (! class_exists($modelClass)) {
            throw new \InvalidArgumentException("Modelo {$modelClass} não encontrado.");
        }

        return new $modelClass;
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?Model
    {
        try {
            return $this->getModel()->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(): Collection
    {
        return $this->getModel()->all();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        return $this->getModel()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): ?Model
    {
        $model = $this->find($id);

        if (! $model) {
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

        if (! $model) {
            return false;
        }

        return $model->delete();
    }

    /**
     * Busca múltiplos registros por IDs.
     *
     * @param  array<int>  $ids  Lista de IDs a buscar.
     * @return Collection<Model> Coleção de modelos encontrados.
     */
    public function findMany(array $ids): Collection
    {
        return $this->getModel()->whereIn('id', $ids)->get();
    }

    /**
     * Conta total de registros.
     *
     * @return int Total de registros.
     */
    public function count(): int
    {
        return $this->getModel()->count();
    }

    /**
     * Verifica se existe registro com determinado ID.
     *
     * @param  int  $id  ID a verificar.
     * @return bool True se existe.
     */
    public function exists(int $id): bool
    {
        return $this->getModel()->where('id', $id)->exists();
    }
}
