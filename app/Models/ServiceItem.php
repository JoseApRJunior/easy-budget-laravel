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
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo ServiceItem.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'  => 'required|integer|exists:tenants,id',
            'service_id' => 'required|integer|exists:services,id',
            'product_id' => 'required|integer|exists:products,id',
            'unit_value' => 'required|numeric|min:0|max:999999.99',
            'quantity'   => 'required|integer|min:1|max:999999',
        ];
    }

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
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();

        static::saving( function ( ServiceItem $model ) {
            // Calculate total as quantity * unit_value using safe decimal math
            $quantity  = $model->quantity ?? 0;
            $unitValue = $model->unit_value ?? 0;

            // Use bcmath if available for precise decimal calculations
            if ( function_exists( 'bcmul' ) ) {
                $computed = bcmul( (string) $unitValue, (string) $quantity, 2 );
            } else {
                // Fallback to number_format for precise decimal handling
                $computed = number_format( $unitValue * $quantity, 2, '.', '' );
            }

            $model->total = $computed;
        } );
    }

    /**
     * Get the calculated total (quantity * unit_value).
     */
    public function getCalculatedTotalAttribute(): string
    {
        // Use bcmath if available for precise decimal calculations
        if ( function_exists( 'bcmul' ) ) {
            return bcmul( (string) $this->unit_value, (string) $this->quantity, 2 );
        }

        // Fallback to number_format for precise decimal handling
        return number_format( $this->unit_value * $this->quantity, 2, '.', '' );
    }

    /**
     * Get the formatted total as currency.
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format( $this->total, 2, ',', '.' );
    }

}
