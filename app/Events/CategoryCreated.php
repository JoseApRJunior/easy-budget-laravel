<?php

declare(strict_types=1);

namespace App\Events;

class CategoryCreated
{
    public function __construct(public int $categoryId) {}
}
