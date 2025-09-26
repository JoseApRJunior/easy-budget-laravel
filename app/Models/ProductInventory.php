<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductInventory extends Model
{
    use HasFactory;
    use TenantScoped;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_inventory';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'product_id',
        'quantity',
        'min_quantity',
        'max_quantity',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity'     => 0,
        'min_quantity' => 0,
        'max_quantity' => null,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'    => 'integer',
        'product_id'   => 'integer',
        'quantity'     => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'created_at'   => 'immutable_datetime',
        'updated_at'   => 'datetime',
    ];

    /**
     * Regras de validação para o modelo ProductInventory.
     *
     * @return array<string, string>
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'    => 'required|exists:tenants,id',
            'product_id'   => 'required|exists:products,id',
            'quantity'     => 'required|integer|min:0',
            'min_quantity' => 'nullable|integer|min:0',
            'max_quantity' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Get the tenant that owns the ProductInventory.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the product that owns the ProductInventory.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo( Product::class);
    }

    // ==================== SCOPES ÚTEIS ====================

    /**
     * Scope para filtrar inventário por produto.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $productId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByProduct( $query, $productId )
    {
        return $query->where( 'product_id', $productId );
    }

    /**
     * Scope para filtrar inventário por tenant.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $tenantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTenant( $query, $tenantId )
    {
        return $query->where( 'tenant_id', $tenantId );
    }

    /**
     * Scope para produtos com estoque baixo.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowStock( $query )
    {
        return $query->whereRaw( 'quantity <= min_quantity' );
    }

    /**
     * Scope para produtos com estoque alto.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighStock( $query )
    {
        return $query->whereRaw( 'quantity >= max_quantity' );
    }

    // ==================== MÉTODOS DE NEGÓCIO ====================

    /**
     * Verifica se o produto está com estoque baixo.
     *
     * @return bool
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_quantity;
    }

    /**
     * Verifica se o produto está com estoque alto.
     *
     * @return bool
     */
    public function isHighStock(): bool
    {
        return $this->max_quantity && $this->quantity >= $this->max_quantity;
    }

    /**
     * Verifica se o produto está dentro da faixa ideal de estoque.
     *
     * @return bool
     */
    public function isInOptimalRange(): bool
    {
        return !$this->isLowStock() && !$this->isHighStock();
    }

    /**
     * Retorna o status do estoque como string.
     *
     * @return string
     */
    public function getStockStatusAttribute(): string
    {
        if ( $this->isLowStock() ) {
            return 'Baixo';
        }

        if ( $this->isHighStock() ) {
            return 'Alto';
        }

        return 'Ideal';
    }

    /**
     * Retorna a porcentagem de utilização do estoque.
     *
     * @return float
     */
    public function getStockUtilizationPercentageAttribute(): float
    {
        if ( !$this->max_quantity ) {
            return 0;
        }

        return round( ( $this->quantity / $this->max_quantity ) * 100, 2 );
    }

}
