<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Plan;
use App\Repositories\Abstracts\AbstractGlobalRepository;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositório para operações de planos globais
 *
 * Implementa métodos básicos necessários pela arquitetura
 * e métodos específicos para gerenciamento de planos
 */
class PlanRepository extends AbstractGlobalRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): \Illuminate\Database\Eloquent\Model
    {
        return new Plan();
    }

    /**
     * {@inheritdoc}
     */
    public function find( int $id ): ?Plan
    {
        return Plan::find( $id );
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(): Collection
    {
        return Plan::all();
    }

    /**
     * {@inheritdoc}
     */
    public function create( array $data ): Plan
    {
        return Plan::create( $data );
    }

    /**
     * {@inheritdoc}
     */
    public function update( int $id, array $data ): ?Plan
    {
        $plan = Plan::find( $id );

        if ( !$plan ) {
            return null;
        }

        $plan->update( $data );
        return $plan;
    }

    /**
     * {@inheritdoc}
     */
    public function delete( int $id ): bool
    {
        return Plan::destroy( $id ) > 0;
    }

    // --------------------------------------------------------------------------
    // MÉTODOS ESPECÍFICOS DE NEGÓCIO PARA PLANOS
    // --------------------------------------------------------------------------

    /**
     * Encontra planos ativos
     */
    public function findActive(): mixed
    {
        return Plan::where( 'status', true )->get();
    }

    /**
     * Encontra plano por slug
     */
    public function findBySlug( string $slug ): mixed
    {
        return Plan::where( 'slug', $slug )->first();
    }

    /**
     * Encontra planos ordenados por preço
     */
    public function findOrderedByPrice( string $direction = 'asc' ): mixed
    {
        return Plan::orderBy( 'price', $direction )->get();
    }

    /**
     * Valida se nome do plano é único
     */
    public function validateUniqueName( string $name, ?int $excludeId = null ): bool
    {
        $query = Plan::where( 'name', $name );

        if ( $excludeId ) {
            $query = $query->where( 'id', '!=', $excludeId );
        }

        return $query->count() === 0;
    }

    /**
     * Encontra planos que permitem determinado número de orçamentos
     */
    public function findByAllowedBudgets( int $budgetCount ): mixed
    {
        if ( $budgetCount < 0 ) {
            throw new \InvalidArgumentException( 'Budget count must be non-negative' );
        }

        return Plan::where( 'max_budgets', '>=', $budgetCount )
            ->where( 'status', true )
            ->get();
    }

    /**
     * Encontra planos que permitem determinado número de clientes
     */
    public function findByAllowedClients( int $clientCount ): mixed
    {
        if ( $clientCount < 0 ) {
            throw new \InvalidArgumentException( 'Client count must be non-negative' );
        }

        return Plan::where( 'max_clients', '>=', $clientCount )
            ->where( 'status', true )
            ->get();
    }

    /**
     * Salva uma assinatura de plano
     */
    public function saveSubscription( $subscription ): mixed
    {
        $subscription->save();
        return $subscription;
    }

}
