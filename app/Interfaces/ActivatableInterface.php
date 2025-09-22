<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Support\ServiceResult;

/**
 * Interface opcional para services que trabalham com entidades ativáveis.
 *
 * Services que implementam esta interface podem listar entidades ativas
 * tanto para entidades tenant-aware quanto globais. Métodos separados para
 * cada contexto evitam poluição da interface base.
 */
interface ActivatableInterface
{
    /**
     * Lista entidades ativas para tenant-aware services
     *
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros adicionais
     * @param array|null $orderBy Ordenação opcional
     * @param int|null $limit Limite de resultados
     * @return ServiceResult Resultado com lista de entidades ativas
     */
    public function listActiveByTenantId(
        int $tenantId,
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): ServiceResult;

    /**
     * Lista entidades ativas para services globais
     *
     * @param array $filters Filtros adicionais
     * @param array|null $orderBy Ordenação opcional
     * @param int|null $limit Limite de resultados
     * @return ServiceResult Resultado com lista de entidades ativas
     */
    public function listActive(
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): ServiceResult;
}
