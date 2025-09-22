<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Support\ServiceResult;

/**
 * Interface opcional para services que precisam de funcionalidade de paginação.
 *
 * Services que implementam esta interface podem fornecer paginação tanto para entidades
 * tenant-aware quanto globais. Métodos separados para cada contexto evitam poluição
 * da interface base.
 */
interface PaginatableInterface
{
    /**
     * Lista entidades com paginação para tenant-aware services
     *
     * @param int $tenantId ID do tenant
     * @param int $page Página atual (inicia em 1)
     * @param int $perPage Itens por página
     * @param array $filters Filtros opcionais
     * @param array|null $orderBy Ordenação opcional
     * @return ServiceResult Resultado com dados paginados e metadados de paginação
     */
    public function paginateByTenantId(
        int $tenantId,
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        ?array $orderBy = null,
    ): ServiceResult;

    /**
     * Lista entidades com paginação para services globais
     *
     * @param int $page Página atual (inicia em 1)
     * @param int $perPage Itens por página
     * @param array $filters Filtros opcionais
     * @param array|null $orderBy Ordenação opcional
     * @return ServiceResult Resultado com dados paginados e metadados de paginação
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        ?array $orderBy = null,
    ): ServiceResult;
}
