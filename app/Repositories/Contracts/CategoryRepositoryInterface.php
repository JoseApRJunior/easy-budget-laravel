<?php
declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface CategoryRepositoryInterface extends TenantRepositoryInterface
{
    public function findByTenantAndSlug(string $slug): ?Model;
    public function listActive(?array $orderBy = null): Collection;
}

