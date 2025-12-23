<?php

declare(strict_types=1);

namespace App\Events;

class CategoryDeleted
{
    public function __construct(public int $categoryId) {}
}

