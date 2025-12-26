<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Common\PlanDTO;
use App\Models\Plan;
use App\Repositories\Abstracts\AbstractGlobalRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório para operações de planos globais
 *
 * Implementa métodos básicos necessários pela arquitetura
 * e métodos específicos para gerenciamento de planos
 */
class PlanRepository extends AbstractGlobalRepository
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Plan;
    }

    /**
     * Cria um novo plano a partir de um DTO.
     */
    public function createFromDTO(PlanDTO $dto): Plan
    {
        return $this->model->newQuery()->create($dto->toArray());
    }

    /**
     * Atualiza um plano a partir de um DTO.
     */
    public function updateFromDTO(int $id, PlanDTO $dto): bool
    {
        $plan = $this->find($id);
        if (! $plan) {
            return false;
        }

        return $plan->update(array_filter($dto->toArray(), fn ($value) => $value !== null));
    }

    // --------------------------------------------------------------------------
    // MÉTODOS ESPECÍFICOS DE NEGÓCIO PARA PLANOS
    // --------------------------------------------------------------------------

    /**
     * Encontra um plano gratuito ativo
     */
    public function findFreeActive(): ?Plan
    {
        return $this->model->newQuery()->where('status', true)->where('price', 0.00)->first();
    }

    /**
     * Encontra planos ativos
     */
    public function findActive(): Collection
    {
        return $this->model->newQuery()->where('status', true)->get();
    }

    /**
     * Encontra plano por slug
     */
    public function findBySlug(string $slug): ?Plan
    {
        return $this->model->newQuery()->where('slug', $slug)->first();
    }

    /**
     * Encontra planos ordenados por preço
     */
    public function findOrderedByPrice(string $direction = 'asc'): Collection
    {
        return $this->model->newQuery()->orderBy('price', $direction)->get();
    }

    /**
     * Valida se nome do plano é único
     */
    public function validateUniqueName(string $name, ?int $excludeId = null): bool
    {
        return ! $this->model->newQuery()
            ->where('name', $name)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    /**
     * Encontra planos que permitem determinado número de orçamentos
     */
    public function findByAllowedBudgets(int $budgetCount): Collection
    {
        if ($budgetCount < 0) {
            throw new \InvalidArgumentException('Budget count must be non-negative');
        }

        return $this->model->newQuery()
            ->where('max_budgets', '>=', $budgetCount)
            ->where('status', true)
            ->get();
    }

    /**
     * Encontra planos que permitem determinado número de clientes
     */
    public function findByAllowedClients(int $clientCount): Collection
    {
        if ($clientCount < 0) {
            throw new \InvalidArgumentException('Client count must be non-negative');
        }

        return $this->model->newQuery()
            ->where('max_clients', '>=', $clientCount)
            ->where('status', true)
            ->get();
    }

    /**
     * Salva uma assinatura de plano
     */
    public function saveSubscription($subscription): mixed
    {
        $subscription->save();

        return $subscription;
    }

    /**
     * Retorna planos paginados com filtros avançados
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('slug', 'like', '%'.$filters['search'].'%')
                    ->orWhere('description', 'like', '%'.$filters['search'].'%');
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (bool) $filters['status']);
        }

        if (! empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if (! empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        return $query->orderBy('price', 'asc')->paginate($perPage);
    }

    /**
     * Conta planos ativos
     */
    public function countActive(): int
    {
        return $this->model->newQuery()->where('status', true)->count();
    }

    /**
     * Verifica se plano pode ser desativado/deletado
     */
    public function canBeDeactivatedOrDeleted(int $id): bool
    {
        return ! $this->model->newQuery()->where('id', $id)->has('planSubscriptions')->exists();
    }
}
