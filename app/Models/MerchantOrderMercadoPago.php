<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MerchantOrderMercadoPago extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'merchant_orders_mercado_pago';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'provider_id',
        'merchant_order_id',
        'plan_subscription_id',
        'status',
        'order_status',
        'total_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'provider_id' => 'integer',
        'plan_subscription_id' => 'integer',
        'total_amount' => 'decimal:2',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    /**
     * Get the tenant that owns the MerchantOrderMercadoPago.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the provider that owns the MerchantOrderMercadoPago.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo( Provider::class);
    }

    /**
     * Get the plan subscription that owns the MerchantOrderMercadoPago.
     */
    public function planSubscription(): BelongsTo
    {
        return $this->belongsTo( PlanSubscription::class);
    }

}
