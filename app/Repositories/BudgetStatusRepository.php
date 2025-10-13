<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BudgetStatus;
use App\Repositories\Abstracts\AbstractGlobalRepository;

class BudgetStatusRepository extends AbstractGlobalRepository
{
    protected string $modelClass = BudgetStatus::class;

    /**
     * Busca status por slug
     */
    public function findBySlug( string $slug ): ?BudgetStatus
    {
        return $this->findOneBy( [ 'slug' => $slug ] );
    }

    /**
     * Busca status ativos
     */
    public function findActive( ?array $orderBy = null, ?int $limit = null ): array
    {
        return $this->findBy( [ 'active' => true ], $orderBy, $limit );
    }

    /**
     * Busca status ordenados
     */
    public function findOrderedBy( string $field, string $direction = 'asc', ?int $limit = null ): array
    {
        return $this->findOrderedBy( $field, $direction, $limit );
    }

}
