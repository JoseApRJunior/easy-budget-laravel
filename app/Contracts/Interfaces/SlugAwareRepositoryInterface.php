<?php

declare(strict_types=1);

namespace App\Contracts;

use App\database\entitiesORM\EntityORMInterface;

interface SlugAwareRepositoryInterface
{
    /**
     * Verifica se existe uma entidade com o slug especificado.
     *
     * @param string $slug O slug a ser verificado
     * @param int|null $tenantId O ID do tenant (null para repositórios globais)
     * @param int|null $excludeId O ID da entidade a excluir da verificação (para updates)
     * @return bool True se existe, false caso contrário
     */
    public function existsBySlug( string $slug, ?int $tenantId = null, ?int $excludeId = null ): bool;
}
