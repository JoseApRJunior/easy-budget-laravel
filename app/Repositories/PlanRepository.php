<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Plan;
use App\Repositories\Abstracts\AbstractGlobalRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositório para operações de planos globais
 *
 * Implementa métodos específicos para gerenciamento de planos
 * sem isolamento por tenant (dados globais)
 */
class PlanRepository extends AbstractGlobalRepository
{
    protected string $modelClass = Plan::class;

    /**
     * Cria uma nova instância do modelo Plan
     *
     * @return Plan
     */
    protected function makeModel(): Plan
    {
        return new Plan();
    }

    /**
     * Encontra planos ativos
     *
     * @return Collection Coleção de planos ativos
     */
    public function findActive(): Collection
    {
        return $this->findByCriteria( [ 'status' => true ] );
    }

    /**
     * Encontra plano por slug
     *
     * @param string $slug Slug do plano
     * @return Plan|null Plano encontrado ou null
     */
    public function findBySlug( string $slug ): ?Plan
    {
        return $this->findOneByCriteria( [ 'slug' => $slug ] );
    }

    /**
     * Encontra planos ordenados por preço
     *
     * @param string $direction Direção da ordenação (asc/desc)
     * @return Collection Coleção de planos ordenados
     */
    public function findOrderedByPrice( string $direction = 'asc' ): Collection
    {
        return $this->newQuery()->orderBy( 'price', $direction )->get();
    }

    /**
     * Valida se nome do plano é único
     *
     * @param string $name Nome a ser verificado
     * @param int|null $excludeId ID do plano a ser excluído da verificação
     * @return bool True se é único, false caso contrário
     */
    public function validateUniqueName( string $name, ?int $excludeId = null ): bool
    {
        return $this->validateUnique( 'name', $name, $excludeId );
    }

    /**
     * Encontra planos que permitem determinado número de orçamentos
     *
     * @param int $budgetCount Número de orçamentos
     * @return Collection Coleção de planos que atendem o critério
     * @throws \InvalidArgumentException Se budgetCount for negativo
     */
    public function findByAllowedBudgets( int $budgetCount ): Collection
    {
        if ( $budgetCount < 0 ) {
            throw new \InvalidArgumentException( 'Budget count must be non-negative' );
        }

        $result = $this->newQuery()
            ->where( 'max_budgets', '>=', $budgetCount )
            ->where( 'status', true )
            ->get();

        $this->resetIfNeeded();
        return $result;
    }

    /**
     * Encontra planos que permitem determinado número de clientes
     *
     * @param int $clientCount Número de clientes
     * @return Collection Coleção de planos que atendem o critério
     * @throws \InvalidArgumentException Se clientCount for negativo
     */
    public function findByAllowedClients( int $clientCount ): Collection
    {
        if ( $clientCount < 0 ) {
            throw new \InvalidArgumentException( 'Client count must be non-negative' );
        }

        $result = $this->newQuery()
            ->where( 'max_clients', '>=', $clientCount )
            ->where( 'status', true )
            ->get();

        $this->resetIfNeeded();
        return $result;
    }

}
