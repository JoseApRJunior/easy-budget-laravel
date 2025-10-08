<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo CustomerTag - Sistema de tags para categorização de clientes
 *
 * Gerencia tags personalizáveis que podem ser atribuídas aos clientes
 * para facilitar a organização e filtragem.
 */
class CustomerTag extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'customer_tags';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'color',
        'description',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id'   => 'integer',
        'name'        => 'string',
        'color'       => 'string',
        'description' => 'string',
        'is_active'   => 'boolean',
        'sort_order'  => 'integer',
        'created_at'  => 'immutable_datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Regras de validação para o modelo CustomerTag.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'name'        => 'required|string|max:100|unique:customer_tags,name',
            'color'       => 'required|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer|min:0',
        ];
    }

    /**
     * Get the tenant that owns the CustomerTag.
     */
    public function tenant()
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the customers associated with this tag.
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany( Customer::class, 'customer_tag_assignments' );
    }

    /**
     * Scope para buscar apenas tags ativas.
     */
    public function scopeActive( $query )
    {
        return $query->where( 'is_active', true );
    }

    /**
     * Scope para ordenar por sort_order e name.
     */
    public function scopeOrdered( $query )
    {
        return $query->orderBy( 'sort_order' )->orderBy( 'name' );
    }

    /**
     * Get the tag's color attribute with default fallback.
     */
    public function getColorAttribute( ?string $value ): string
    {
        return $value ?? '#6B7280';
    }

    /**
     * Check if the tag is in use by any customer.
     */
    public function isInUse(): bool
    {
        return $this->customers()->exists();
    }

    /**
     * Get the usage count for this tag.
     */
    public function getUsageCountAttribute(): int
    {
        return $this->customers()->count();
    }

}
