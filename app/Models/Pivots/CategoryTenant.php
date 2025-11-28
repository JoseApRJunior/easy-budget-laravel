<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryTenant extends Pivot
{
    protected $table = 'category_tenant';

    protected $fillable = [
        'category_id',
        'tenant_id',
        'is_default',
        'is_custom',
    ];

    public $timestamps = true;

    protected $casts = [
        'is_default' => 'boolean',
        'is_custom' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Apenas logs de auditoria
        // Lógica de negócio (como set default) está em CategoryManagementService

        static::created(function (CategoryTenant $pivot) {
            Log::info('category_tenant created', [
                'tenant_id' => $pivot->tenant_id,
                'category_id' => $pivot->category_id,
                'is_default' => (bool) $pivot->is_default,
                'is_custom' => (bool) $pivot->is_custom,
            ]);
        });

        static::updated(function (CategoryTenant $pivot) {
            Log::info('category_tenant updated', [
                'tenant_id' => $pivot->tenant_id,
                'category_id' => $pivot->category_id,
                'is_default' => (bool) $pivot->is_default,
                'is_custom' => (bool) $pivot->is_custom,
            ]);
        });
    }
}
