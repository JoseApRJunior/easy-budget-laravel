<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface para serviços que suportam geração de slugs
 */
interface SlugableInterface
{
    /**
     * Gera um slug único para uma entidade
     */
    public function generateSlug( string $name, ?int $excludeId = null ): string;

    /**
     * Valida se um slug é único
     */
    public function isSlugUnique( string $slug, ?int $excludeId = null ): bool;
}
