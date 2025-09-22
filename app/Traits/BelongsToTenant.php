<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Get the tenant that owns the permission.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Scope a query to only include permissions for a given tenant.
     */
    public function scopeForTenant( Builder $query, Tenant $tenant ): Builder
    {
        return $query->where( 'tenant_id', $tenant->id );
    }

}