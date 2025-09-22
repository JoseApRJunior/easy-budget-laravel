<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Provider;

class ProviderRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected string $modelClass = Provider::class;
}