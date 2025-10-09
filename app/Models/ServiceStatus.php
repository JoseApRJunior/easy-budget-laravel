<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceStatus extends Model
{

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        // Modelo global - não usa tenant scoping
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_statuses';

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
        'slug'        => 'string',
        'name'        => 'string',
        'description' => 'string',
        'color'       => 'string',
        'icon'        => 'string',
        'order_index' => 'integer',
        'is_active'   => 'boolean',
        'created_at'  => 'immutable_datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Regras de validação para o modelo ServiceStatus.
     */
    public static function businessRules(): array
    {
        return [
            'slug'        => 'required|string|max:20|unique:service_statuses,slug',
            'name'        => 'required|string|max:50|unique:service_statuses,name',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|max:7',
            'icon'        => 'nullable|string|max:30',
            'order_index' => 'nullable|integer',
            'is_active'   => 'boolean',
        ];
    }

    /**
     * Get the services for the ServiceStatus.
     */
    public function services(): HasMany
    {
        return $this->hasMany( Service::class, 'service_statuses_id' );
    }

    /**
     * Scope para ordenar status por order_index e nome.
     */
    public function scopeOrdered( $query )
    {
        return $query->orderBy( 'order_index' )
            ->orderBy( 'name' );
    }

    /**
     * Scope para buscar status ativos.
     */
    public function scopeActive( $query )
    {
        return $query->where( 'is_active', true );
    }

}
