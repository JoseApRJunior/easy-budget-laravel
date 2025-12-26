<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\Core\CategoryCacheService;

class ClearCategoryCache
{
    public function __construct(private CategoryCacheService $cache) {}

    public function handle(mixed $event): void
    {
        if (property_exists($event, 'tenantId') && is_int($event->tenantId)) {
            $this->cache->clearForTenant($event->tenantId);
        } else {
            $this->cache->clearAll();
        }
    }
}
