<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Tenant;

class TenantObserver
{
    public function created( Tenant $tenant ): void
    {
        $categories = Category::globalOnly()
            ->orderBy( 'name' )
            ->get( [ 'id' ] );
        $tenant->categories()->syncWithoutDetaching(
            $categories->pluck( 'id' )->toArray(),
        );
    }

}
