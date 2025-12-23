<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BudgetShare;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BudgetShareRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new BudgetShare();
    }

    /**
     * Lista compartilhamentos por orçamento.
     *
     * @param int $budgetId ID do orçamento
     * @param array<string, string>|null $orderBy Ordenação
     * @param int|null $limit Limite de registros
     * @return \Illuminate\Database\Eloquent\Collection<int, BudgetShare> Compartilhamentos encontrados
     */
    public function listByBudget(int $budgetId, ?array $orderBy = null, ?int $limit = null): Collection
    {
        return $this->getAllByTenant(
            ['budget_id' => $budgetId],
            $orderBy,
            $limit,
        );
    }

    /**
     * Lista compartilhamentos ativos.
     *
     * @param array $filters Filtros adicionais
     * @param array<string, string>|null $orderBy Ordenação
     * @param int|null $limit Limite de registros
     * @return \Illuminate\Database\Eloquent\Collection<int, BudgetShare> Compartilhamentos encontrados
     */
    public function listActive(array $filters = [], ?array $orderBy = null, ?int $limit = null): Collection
    {
        $filters['is_active'] = true;
        return $this->getAllByTenant(
            $filters,
            $orderBy,
            $limit,
        );
    }

    /**
     * Busca compartilhamento por token.
     *
     * @param string $token Token de compartilhamento
     * @return BudgetShare|null
     */
    public function findByToken(string $token): ?BudgetShare
    {
        return $this->getByTenant()
            ->where('share_token', $token)
            ->first();
    }

    /**
     * Busca compartilhamento por email.
     *
     * @param string $email Email do destinatário
     * @param int $budgetId ID do orçamento
     * @return BudgetShare|null
     */
    public function findByEmailAndBudget(string $email, int $budgetId): ?BudgetShare
    {
        return $this->getByTenant()
            ->where('email', $email)
            ->where('budget_id', $budgetId)
            ->first();
    }

    /**
     * Conta compartilhamentos por orçamento.
     *
     * @param int $budgetId ID do orçamento
     * @param array $filters Filtros adicionais
     * @return int Número de compartilhamentos
     */
    public function countByBudget(int $budgetId, array $filters = []): int
    {
        $filters['budget_id'] = $budgetId;
        return $this->countByTenant($filters);
    }

    /**
     * Verifica se existe compartilhamento ativo.
     *
     * @param int $budgetId ID do orçamento
     * @param string $email Email do destinatário
     * @return bool
     */
    public function hasActiveShare(int $budgetId, string $email): bool
    {
        return $this->getByTenant()
            ->where('budget_id', $budgetId)
            ->where('email', $email)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Revoga todos os compartilhamentos de um orçamento.
     *
     * @param int $budgetId ID do orçamento
     * @return bool
     */
    public function revokeAllByBudget(int $budgetId): bool
    {
        return DB::transaction(function () use ($budgetId) {
            return $this->getByTenant()
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
        return $this->getByTenant()
            ->where('expires_at', '<', now())
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
