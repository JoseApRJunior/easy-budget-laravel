<?php

declare(strict_types=1);

namespace App\DesignPatterns\NoTenant;

use App\Models\Role; // Assumindo que existe um model Role para dados globais
use App\Repositories\AbstractNoTenantRepository;

/**
 * Exemplo de repositório não tenant-aware para dados globais
 *
 * Demonstra operações com dados compartilhados, como roles e permissions.
 */
class ExampleRepository extends AbstractNoTenantRepository
{
    /**
     * @var string
     */
    protected string $modelClass = Role::class;

    /**
     * Busca roles ordenados por nome
     */
    public function findOrderedByName( ?int $limit = null ): array
    {
        return $this->findOrderedBy( 'name', 'asc', $limit );
    }

}