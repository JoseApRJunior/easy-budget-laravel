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
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
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

    /**
     * Boot the model and set up event listeners.
     */
    protected static function booted(): void
    {
        static::saving( function ( ServiceItem $model ) {
            // Calculate total as quantity * unit_value using safe decimal math
            $quantity  = (int) ( $model->quantity ?? 0 );
            $unitValue = (int) ( $model->unit_value ?? '0' );
            $computed  = 0;

            // Use bcmath if available for precise decimal calculations
            if ( function_exists( 'bcmul' ) ) {
                $computed = bcmul( $unitValue, (int) $quantity, 2 );
            } else {
                // Fallback to number_format for precise decimal handling
                $computed = number_format( ( (float) $unitValue ) * $quantity, 2, '.', '' );
            }

            $model->total = $computed;
        } );
    }

}