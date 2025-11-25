<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Tenant;

class TenantObserver
{
    public function created(Tenant $tenant): void
    {
        $categories = Category::orderBy('name')->get(['id']);
        $tenant->categories()->syncWithoutDetaching(
            $categories->mapWithKeys(function ($c) {
                return [$c->id => ['is_default' => true, 'is_custom' => false]];
            })->toArray()
        );
    }
}

