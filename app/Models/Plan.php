<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Model para representar planos de assinatura.
 */
class Plan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'name',
        'slug',
        'description',
        'price',
        'status',
        'max_budgets',
        'max_clients',
        'features',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'description' => 'string',
        'price'       => 'decimal:2',
        'status'      => 'boolean',
        'max_budgets' => 'integer',
        'max_clients' => 'integer',
        'features'    => 'array',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Get the plan subscriptions for the Plan.
     */
    public function planSubscriptions(): HasMany
    {
        return $this->hasMany( PlanSubscription::class);
    }

}
