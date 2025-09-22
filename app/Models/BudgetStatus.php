<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class BudgetStatus extends Model
{
    /**
     * Scope para obter status ativos.
     * Filtra por is_active = true e ordena por order_index.
     * Substitui funcionalidade do HasEnums trait, adaptado para esta tabela sem coluna 'status'.
     *
     * @param mixed $query
     * @return mixed
     */
    public function scopeActiveStatus( $query )
    {
        return $query->where( 'is_active', true )->orderBy( 'order_index' );
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'budget_statuses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'color',
        'icon',
        'order_index',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'description' => 'string',
        'icon'        => 'string',
        'is_active'   => 'boolean',
        'order_index' => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Get the budgets for the BudgetStatus.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany( Budget::class, 'budget_statuses_id' );
    }

}
