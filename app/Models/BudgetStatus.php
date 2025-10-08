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
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
    }

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
        'created_at'  => 'immutable_datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Regras de validação para o modelo BudgetStatus.
     */
    public static function businessRules(): array
    {
        return [
            'slug'        => 'required|string|max:50|unique:budget_statuses,slug',
            'name'        => 'required|string|max:100|unique:budget_statuses,name',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i',
            'icon'        => 'nullable|string|max:50',
            'order_index' => 'nullable|integer|min:0',
            'is_active'   => 'required|boolean',
        ];
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public const UPDATED_AT = null;

    /**
     * Get the budgets for the BudgetStatus.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany( Budget::class, 'budget_statuses_id' );
    }

}
