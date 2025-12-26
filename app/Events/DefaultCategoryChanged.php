<?php

declare(strict_types=1);

namespace App\Events;

class DefaultCategoryChanged
{
    public function __construct(public int $categoryId, public int $tenantId) {}
}
