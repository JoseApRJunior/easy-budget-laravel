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
        'transaction_amount' => 'decimal:2',
        'start_date'         => 'datetime',
        'end_date'           => 'datetime',
        'transaction_date'   => 'datetime',
        'last_payment_date'  => 'datetime',
        'next_payment_date'  => 'datetime',
        'tenant_id'          => 'integer',
        'provider_id'        => 'integer',
        'plan_id'            => 'integer',
        'created_at'         => 'immutable_datetime',
        'updated_at'         => 'immutable_datetime',
    ];

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

}
