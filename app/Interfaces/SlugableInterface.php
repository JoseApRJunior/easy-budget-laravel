<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Support\ServiceResult;

/**
 * Interface opcional para services que trabalham com slugs.
 *
 * Services que implementam esta interface podem buscar entidades por slug
 * tanto para entidades tenant-aware quanto globais. Métodos separados para
 * cada contexto evitam poluição da interface base.
 */
interface SlugableInterface
{
    /**
     * Busca entidade por slug para tenant-aware services
     *
     * @param string $slug Slug da entidade
     * @param int $tenantId ID do tenant
     * @return ServiceResult Resultado com a entidade encontrada
     */
    public function getBySlugAndTenantId( string $slug, int $tenantId ): ServiceResult;

    /**
     * Busca entidade por slug para services globais
     *
     * @param string $slug Slug da entidade
     * @return ServiceResult Resultado com a entidade encontrada
     */
    public function getBySlug( string $slug ): ServiceResult;
}
