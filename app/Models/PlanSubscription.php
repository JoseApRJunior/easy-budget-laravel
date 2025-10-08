<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Model para representar assinaturas de plano, scoped por tenant.
 */
class PlanSubscription extends Model
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
    const STATUS_ACTIVE    = 'active';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PENDING   = 'pending';
    const STATUS_EXPIRED   = 'expired';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plan_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'status',
        'transaction_amount',
        'start_date',
        'end_date',
        'transaction_date',
        'payment_method',
        'payment_id',
        'public_hash',
        'last_payment_date',
        'next_payment_date',
        'tenant_id',
        'provider_id',
        'plan_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'provider_id'        => 'integer',
        'plan_id'            => 'integer',
        'tenant_id'          => 'integer',
        'status'             => 'string',
        'public_hash'        => 'string',
        'transaction_amount' => 'decimal:2',
        'start_date'         => 'immutable_datetime',
        'end_date'           => 'datetime',
        'payment_method'     => 'string',
        'payment_id'         => 'string',
        'last_payment_date'  => 'datetime',
        'next_payment_date'  => 'datetime',
        'transaction_date'   => 'datetime',
        'created_at'         => 'immutable_datetime',
        'updated_at'         => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Plan.
     */
    public static function businessRules(): array
    {
        return [
            'status'             => 'required|in:' . implode( ',', [
                self::STATUS_ACTIVE,
                self::STATUS_CANCELLED,
                self::STATUS_PENDING,
                self::STATUS_EXPIRED
            ] ),
            'transaction_amount' => 'required|numeric|min:0.01',
            'start_date'         => 'required|date|after_or_equal:today',
            'end_date'           => 'nullable|date|after:start_date',
            'payment_method'     => 'nullable|string|max:50',
            'payment_id'         => 'nullable|string|max:50',
            'last_payment_date'  => 'nullable|date',
            'next_payment_date'  => 'nullable|date',
            'transaction_date'   => 'nullable|date'
        ];
    }

    /**
     * Get the tenant that owns the PlanSubscription.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the provider that owns the PlanSubscription.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo( Provider::class);
    }

    /**
     * Get the plan that owns the PlanSubscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo( Plan::class);
    }

    /**
     * Verifica se a assinatura está ativa.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        $currentDate = new Carbon();
        return $this->status === self::STATUS_ACTIVE
            && $this->end_date
            && $currentDate < $this->end_date;
    }

    /**
     * Verifica se a assinatura está expirada.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $currentDate = new Carbon();
        return $this->status === self::STATUS_EXPIRED
            || ( $this->end_date && $currentDate > $this->end_date );
    }

    /**
     * Calcula os dias restantes da assinatura.
     *
     * @return int|null Retorna os dias restantes ou null se não houver data de fim
     */
    public function getRemainingDays(): ?int
    {
        if ( !$this->end_date ) {
            return null;
        }

        $currentDate = new Carbon();
        $remaining   = $currentDate->diffInDays( $this->end_date, false );

        return $remaining >= 0 ? $remaining : 0;
    }

    /**
     * Verifica se o pagamento está em atraso.
     *
     * @return bool
     */
    public function isPaymentOverdue(): bool
    {
        if ( !$this->next_payment_date ) {
            return false;
        }

        $currentDate = new Carbon();
        return $currentDate > $this->next_payment_date;
    }

    /**
     * Verifica se a assinatura pode ser cancelada.
     *
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        $currentDate = new Carbon();

        // Não pode cancelar se já estiver cancelada ou expirada
        if ( in_array( $this->status, [ self::STATUS_CANCELLED, self::STATUS_EXPIRED ] ) ) {
            return false;
        }

        // Pode cancelar se estiver ativa
        if ( $this->status === self::STATUS_ACTIVE ) {
            return true;
        }

        // Pode cancelar se estiver pendente e não houver data de início no passado
        if ( $this->status === self::STATUS_PENDING ) {
            return $this->start_date && $currentDate < $this->start_date;
        }

        return false;
    }

    /**
     * Obtém o status formatado para exibição.
     *
     * @return string
     */
    public function getFormattedStatusAttribute(): string
    {
        return match ( $this->status ) {
            self::STATUS_ACTIVE    => 'Ativa',
            self::STATUS_CANCELLED => 'Cancelada',
            self::STATUS_PENDING   => 'Pendente',
            self::STATUS_EXPIRED   => 'Expirada',
            default                => 'Desconhecido',
        };
    }

    /**
     * Verifica se a assinatura pode ser renovada.
     *
     * @return bool
     */
    public function canBeRenewed(): bool
    {
        return $this->isExpired() || $this->getRemainingDays() <= 7;
    }

    /**
     * Calcula o valor total pago até o momento.
     *
     * @return float
     */
    public function getTotalPaidAmount(): float
    {
        return $this->transaction_amount ?? 0.00;
    }

    /**
     * Verifica se a assinatura está próxima do vencimento (últimos 7 dias).
     *
     * @return bool
     */
    public function isNearExpiration(): bool
    {
        $remainingDays = $this->getRemainingDays();

        return $remainingDays !== null && $remainingDays <= 7 && $remainingDays > 0;
    }

}
