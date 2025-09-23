<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Modelo para combinar dados de Plan e PlanSubscription.
 *
 * Esta é uma entidade virtual/DTO somente leitura que combina dados
 * de Plan e PlanSubscription através de joins Eloquent para relatórios e consultas.
 * Não depende de uma view física no banco de dados.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $provider_id
 * @property int $plan_id
 * @property string $status
 * @property float $transaction_amount
 * @property Carbon $end_date
 * @property string $slug
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PlansWithPlanSubscription extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get query for PlansWithPlanSubscription data using joins.
     * Since the view doesn't exist, we use Eloquent joins instead.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function buildQuery()
    {
        return PlanSubscription::select( [ 
            'plan_subscriptions.id',
            'plan_subscriptions.tenant_id',
            'plan_subscriptions.provider_id',
            'plan_subscriptions.plan_id',
            'plan_subscriptions.status',
            'plan_subscriptions.transaction_amount',
            'plan_subscriptions.end_date',
            'plans.slug',
            'plans.name',
            'plan_subscriptions.created_at',
            'plan_subscriptions.updated_at',
        ] )
            ->join( 'plans', 'plan_subscriptions.plan_id', '=', 'plans.id' )
            ->with( [ 'tenant', 'provider', 'plan' ] );
    }

    /**
     * Create a new instance from PlanSubscription data.
     *
     * @param PlanSubscription $planSubscription
     * @return static
     */
    public static function fromPlanSubscription( PlanSubscription $planSubscription )
    {
        $instance                     = new static();
        $instance->id                 = $planSubscription->id;
        $instance->tenant_id          = $planSubscription->tenant_id;
        $instance->provider_id        = $planSubscription->provider_id;
        $instance->plan_id            = $planSubscription->plan_id;
        $instance->status             = $planSubscription->status;
        $instance->transaction_amount = $planSubscription->transaction_amount;
        $instance->end_date           = $planSubscription->end_date;
        $instance->slug               = $planSubscription->plan->slug ?? '';
        $instance->name               = $planSubscription->plan->name ?? '';
        $instance->created_at         = $planSubscription->created_at;
        $instance->updated_at         = $planSubscription->updated_at;

        return $instance;
    }

    /**
     * Get all records as a collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAll()
    {
        return static::buildQuery()->get();
    }

    /**
     * Find a subscription with plan by ID.
     *
     * @param int $id
     * @return static|null
     */
    public static function findSubscriptionWithPlan( $id )
    {
        $planSubscription = PlanSubscription::find( $id );

        if ( !$planSubscription ) {
            return null;
        }

        return static::fromPlanSubscription( $planSubscription );
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'tenant_id',
        'provider_id',
        'plan_id',
        'status',
        'transaction_amount',
        'end_date',
        'slug',
        'name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'tenant_id'          => 'integer',
        'provider_id'        => 'integer',
        'plan_id'            => 'integer',
        'transaction_amount' => 'decimal:2',
        'end_date'           => 'datetime',
    ];

    /**
     * Get the tenant that owns the PlansWithPlanSubscription.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the provider that owns the PlansWithPlanSubscription.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo( Provider::class);
    }

    /**
     * Get the plan that owns the PlansWithPlanSubscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo( Plan::class);
    }

    /**
     * Get the plan subscription that owns the PlansWithPlanSubscription.
     *
     * @return HasOne
     */
    public function planSubscription(): HasOne
    {
        return $this->hasOne( PlanSubscription::class, 'id', 'id' );
    }

}
