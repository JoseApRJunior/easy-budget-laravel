<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMercadoPagoPlan extends Model
{
    use HasFactory, TenantScoped, SoftDeletes;

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
    const STATUS_PENDING   = 'pending';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED  = 'refunded';

    /**
     * Payment method constants.
     */
    const PAYMENT_METHOD_CREDIT_CARD   = 'credit_card';
    const PAYMENT_METHOD_DEBIT_CARD    = 'debit_card';
    const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_METHOD_TICKET        = 'ticket';
    const PAYMENT_METHOD_PIX           = 'pix';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_mercado_pago_plans';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

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
        'payment_id'           => 'string',
        'provider_id'          => 'integer',
        'tenant_id'            => 'integer',
        'plan_subscription_id' => 'integer',
        'status'               => 'string',
        'payment_method'       => 'string',
        'transaction_amount'   => 'decimal:2',
        'transaction_date'     => 'datetime',
        'created_at'           => 'immutable_datetime',
        'updated_at'           => 'datetime',
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

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus( $query, string $status )
    {
        return $query->where( 'status', $status );
    }

    /**
     * Scope to filter by payment method.
     */
    public function scopeByPaymentMethod( $query, string $paymentMethod )
    {
        return $query->where( 'payment_method', $paymentMethod );
    }

    /**
     * Check if the payment is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the payment is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if the payment is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if the payment is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

}
