<?php

declare(strict_types=1);

namespace App\DesignPatterns\WithTenant;

use App\Models\Example; // Placeholder - substituir pelo modelo real quando implementado
use App\Repositories\AbstractRepository;

/**
 * Exemplo de repositório tenant-aware usando AbstractRepository
 *
 * Demonstra operações com isolamento de tenant_id
 */
class ExampleRepository extends AbstractRepository
{
    protected string $modelClass = Example::class;

    /**
     * Exemplo de operação específica com tenant
     *
     * @param array $data Dados do exemplo
     * @param int $tenantId ID do tenant
     * @return Example|false
     */
    public function createTenantExample( array $data, int $tenantId ): Example|false
    {
        $entity            = $this->newModel( $data );
        $entity->tenant_id = $tenantId;
        return $this->save( $entity, $tenantId );
    }

    /**
     * Exemplo de busca por tenant
     *
     * @param int $tenantId ID do tenant
     * @param array $criteria Critérios
     * @return array
     */
    public function findExamplesByTenantId( int $tenantId, array $criteria = [] ): array
    {
        return $this->findByAndTenantId( $criteria, $tenantId );
    }

}
