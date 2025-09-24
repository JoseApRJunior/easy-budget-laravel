<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PaymentMercadoPagoPlan extends Model
{
    use TenantScoped;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_mercado_pago_plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_id',
        'tenant_id',
        'provider_id',
        'plan_subscription_id',
        'status',
        'payment_method',
        'transaction_amount',
        'transaction_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'            => 'integer',
        'provider_id'          => 'integer',
        'plan_subscription_id' => 'integer',
        'transaction_amount'   => 'decimal:2',
        'transaction_date'     => 'datetime',
        'created_at'           => 'immutable_datetime',
        'updated_at'           => 'immutable_datetime',
    ];

    /**
     * Get the tenant that owns the PaymentMercadoPagoPlan.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the provider that owns the PaymentMercadoPagoPlan.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo( Provider::class);
    }

    /**
     * Get the plan subscription that owns the PaymentMercadoPagoPlan.
     */
    public function planSubscription(): BelongsTo
    {
        return $this->belongsTo( PlanSubscription::class);
    }

}