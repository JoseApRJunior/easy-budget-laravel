<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Budget\BudgetShareDTO;
use App\Models\BudgetShare;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BudgetShareRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new BudgetShare;
    }

    /**
     * Cria um novo compartilhamento a partir de um DTO.
     */
    public function createFromDTO(BudgetShareDTO $dto): BudgetShare
    {
        return $this->model->newQuery()->create($dto->toArray());
    }

    /**
     * Atualiza um compartilhamento a partir de um DTO.
     */
    public function updateFromDTO(int $id, BudgetShareDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    /**
     * Lista compartilhamentos por orçamento.
     *
     * @param  int  $budgetId  ID do orçamento
     * @param  array<string, string>|null  $orderBy  Ordenação
     * @param  int|null  $limit  Limite de registros
     * @return \Illuminate\Database\Eloquent\Collection<int, BudgetShare> Compartilhamentos encontrados
     */
    public function listByBudget(int $budgetId, ?array $orderBy = null, ?int $limit = null): Collection
    {
        return $this->model->newQuery()
            ->where('budget_id', $budgetId)
            ->when($orderBy, function ($query) use ($orderBy) {
                foreach ($orderBy as $column => $direction) {
                    $query->orderBy($column, $direction);
                }
            })
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get();
    }

    /**
     * Lista compartilhamentos ativos.
     *
     * @param  array  $filters  Filtros adicionais
     * @param  array<string, string>|null  $orderBy  Ordenação
     * @param  int|null  $limit  Limite de registros
     * @return \Illuminate\Database\Eloquent\Collection<int, BudgetShare> Compartilhamentos encontrados
     */
    public function listActive(array $filters = [], ?array $orderBy = null, ?int $limit = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('is_active', true);

        $this->applyFilters($query, $filters);

        if ($orderBy) {
            foreach ($orderBy as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Busca compartilhamento por token.
     *
     * @param  string  $token  Token de compartilhamento
     */
    public function findByToken(string $token): ?BudgetShare
    {
        return $this->model->newQuery()
            ->where('share_token', $token)
            ->first();
    }

    /**
     * Busca compartilhamento por email.
     *
     * @param  string  $email  Email do destinatário
     * @param  int  $budgetId  ID do orçamento
     */
    public function findByEmailAndBudget(string $email, int $budgetId): ?BudgetShare
    {
        return $this->model->newQuery()
            ->where('recipient_email', $email)
            ->where('budget_id', $budgetId)
            ->first();
    }

    /**
     * Conta compartilhamentos por orçamento.
     *
     * @param  int  $budgetId  ID do orçamento
     * @param  array  $filters  Filtros adicionais
     * @return int Número de compartilhamentos
     */
    public function countByBudget(int $budgetId, array $filters = []): int
    {
        $query = $this->model->newQuery()
            ->where('budget_id', $budgetId);

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * Verifica se existe compartilhamento ativo.
     *
     * @param  int  $budgetId  ID do orçamento
     * @param  string  $email  Email do destinatário
     */
    public function hasActiveShare(int $budgetId, string $email): bool
    {
        return $this->model->newQuery()
            ->where('budget_id', $budgetId)
            ->where('recipient_email', $email)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Revoga todos os compartilhamentos de um orçamento.
     *
     * @param  int  $budgetId  ID do orçamento
     */
    public function revokeAllByBudget(int $budgetId): bool
    {
        return DB::transaction(function () use ($budgetId) {
            return $this->model->newQuery()
                ->where('budget_id', $budgetId)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        });
    }

    /**
     * Limpa compartilhamentos expirados.
     *
     * @return int Número de registros afetados
     */
    public function cleanupExpiredShares(): int
    {
        return $this->model->newQuery()
            ->where('expires_at', '<', now())
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
