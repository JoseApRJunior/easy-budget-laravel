<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ServiceItem extends Model
{
    use TenantScoped;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'tenant_id',
        'service_id',
        'product_id',
        'unit_value',
        'quantity',
        'total',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'tenant_id'  => 'integer',
        'service_id' => 'integer',
        'product_id' => 'integer',
        'unit_value' => 'decimal:2',
        'quantity'   => 'integer',
        'total'      => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the service that owns the ServiceItem.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo( Service::class);
    }

    /**
     * Get the tenant that owns the ServiceItem.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the product that owns the ServiceItem.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo( Product::class);
    }

}
