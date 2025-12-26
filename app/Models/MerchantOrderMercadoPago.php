<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantOrderMercadoPago extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * Status constants.
     */
    const STATUS_OPEN = 'open';

    const STATUS_CLOSED = 'closed';

    const STATUS_EXPIRED = 'expired';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * Order status constants.
     */
    const ORDER_STATUS_PAYMENT_REQUIRED = 'payment_required';

    const ORDER_STATUS_PAYMENT_IN_PROCESS = 'payment_in_process';

    const ORDER_STATUS_PAYMENT_APPROVED = 'payment_approved';

    const ORDER_STATUS_PAYMENT_AUTHORIZED = 'payment_authorized';

    const ORDER_STATUS_PAYMENT_IN_MEDATION = 'payment_in_mediation';

    const ORDER_STATUS_PAYMENT_REJECTED = 'payment_rejected';

    const ORDER_STATUS_PAYMENT_CANCELLED = 'payment_cancelled';

    const ORDER_STATUS_PAYMENT_UNKNOWN = 'payment_unknown';

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
        'merchant_order_id' => 'string',
        'provider_id' => 'integer',
        'tenant_id' => 'integer',
        'plan_subscription_id' => 'integer',
        'status' => 'string',
        'order_status' => 'string',
        'total_amount' => 'decimal:2',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Plan.
     */
    public static function businessRules(): array
    {
        return [

        ];
    }

    /**
     * Obtém o tenant proprietário do MerchantOrderMercadoPago.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Obtém o provider proprietário do MerchantOrderMercadoPago.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Obtém a assinatura do plano proprietária do MerchantOrderMercadoPago.
     */
    public function planSubscription(): BelongsTo
    {
        return $this->belongsTo(PlanSubscription::class);
    }
}
