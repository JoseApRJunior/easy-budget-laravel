<?php

declare(strict_types=1);

namespace App\Services\Core;

use Illuminate\Support\Facades\Cache;

class CategoryCacheService
{
    private const TTL = 3600;

    public function getForTenant(int $tenantId, callable $callback)
    {
        $key = "categories:tenant:{$tenantId}";

        return Cache::tags(['categories', "tenant:{$tenantId}"])
            ->remember($key, self::TTL, $callback);
    }

    public function clearForTenant(int $tenantId): void
    {
        Cache::tags(["tenant:{$tenantId}"])->flush();
    }

    public function clearAll(): void
    {
        Cache::tags(['categories'])->flush();
    }
}
