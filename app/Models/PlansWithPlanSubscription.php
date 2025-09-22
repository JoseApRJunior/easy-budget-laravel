<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Modelo para a view PlansWithPlanSubscription.
 *
 * Esta é uma entidade de view/DTO somente leitura que combina dados
 * de Plan e PlanSubscription para relatórios e consultas.
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
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plans_with_plan_subscription';

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
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

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
     * Este relacionamento utiliza chave primária compartilhada (id) para conectar
     * a view PlansWithPlanSubscription com a tabela PlanSubscription.
     * Utilizado para relatórios que precisam acessar dados detalhados da assinatura.
     *
     * @return HasOne
     */
    public function planSubscription(): HasOne
    {
        return $this->hasOne(PlanSubscription::class, 'id', 'id');
    }

    /**
     * Override save method to make this model read-only.
     *
     * @param  array  $options
     * @return bool
     *
     * @throws \Exception
     */
    public function save( array $options = [] ): bool
    {
        throw new \Exception( 'This model is read-only and cannot be saved.' );
    }

    /**
     * Override update method to make this model read-only.
     *
     * @param  array  $attributes
     * @param  array  $options
     * @return bool
     *
     * @throws \Exception
     */
    public function update( array $attributes = [], array $options = [] ): bool
    {
        throw new \Exception( 'This model is read-only and cannot be updated.' );
    }

    /**
     * Override delete method to make this model read-only.
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete(): ?bool
    {
        throw new \Exception( 'This model is read-only and cannot be deleted.' );
    }

}
