<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface para repositórios sem tenant
 *
 * @deprecated Use GlobalRepositoryInterface instead
 */
interface RepositoryNoTenantInterface extends GlobalRepositoryInterface
{
    //
}
