<?php

declare(strict_types=1);

namespace App\Events;

class CategoryUpdated
{
    public function __construct(public int $categoryId) {}
}
