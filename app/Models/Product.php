<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Product extends Model
{
    use HasFactory;
    use TenantScoped;

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'price',
        'active',
        'code',
        'image',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'   => 'integer',
        'name'        => 'string',
        'description' => 'string',
        'price'       => 'decimal:2',
        'active'      => 'boolean',
        'code'        => 'string',
        'image'       => 'string',
        'created_at'  => 'immutable_datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Product.
     *
     * @return array<string, string>
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'price'       => 'required|numeric|min:0',
            'code'        => 'nullable|string|max:50',
            'active'      => 'boolean',
            'image'       => 'nullable|string|max:255',
            'tenant_id'   => 'required|exists:tenants,id',
        ];
    }

    /**
     * Validação personalizada para code único por tenant.
     * Esta validação deve ser usada no contexto de um request onde o tenant_id está disponível.
     *
     * @param  string|null  $code
     * @param  int|null  $excludeId
     * @return string
     */
    public static function validateUniqueCodeRule( ?string $code, ?int $excludeId = null ): string
    {
        if ( empty( $code ) ) {
            return 'nullable|string|max:50';
        }

        $rule = 'unique:products,code';

        if ( $excludeId ) {
            $rule .= ',' . $excludeId . ',id';
        }

        return $rule . ',tenant_id,' . request()->user()->tenant_id;
    }

    /**
     * Get the tenant that owns the Product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Tenant, \App\Models\Product>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Movimentações de inventário deste produto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\InventoryMovement>
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany( InventoryMovement::class);
    }

    /**
     * Controle de inventário do produto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ProductInventory>
     */
    public function productInventory(): HasMany
    {
        return $this->hasMany( ProductInventory::class);
    }

    // ==================== SCOPES ÚTEIS ====================

    /**
     * Scope para filtrar produtos ativos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive( $query )
    {
        return $query->where( 'active', true );
    }

    /**
     * Scope para filtrar produtos por tenant específico.
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
     * Scope para filtrar produtos por faixa de preço.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $min
     * @param  float  $max
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriceRange( $query, $min, $max )
    {
        return $query->whereBetween( 'price', [ $min, $max ] );
    }

    /**
     * Scope para buscar produtos por nome.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByName( $query, $name )
    {
        return $query->where( 'name', 'LIKE', '%' . $name . '%' );
    }

    /**
     * Scope para carregar produtos com dados de inventário.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithInventory( $query )
    {
        return $query->with( [ 'productInventory' ] );
    }

    // ==================== MÉTODOS DE NEGÓCIO ====================

    /**
     * Verifica se o produto está disponível no inventário.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        $totalQuantity = $this->productInventory()
            ->sum( 'quantity' );

        return $totalQuantity > 0;
    }

    /**
     * Alterna o status ativo/inativo do produto.
     *
     * @return bool
     */
    public function toggleActive(): bool
    {
        $this->active = !$this->active;
        return $this->save();
    }

    // ==================== ACCESSORS ====================

    /**
     * Retorna o preço formatado como moeda (BRL).
     *
     * @return string
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format( (float) $this->price, 2, ',', '.' );
    }

    /**
     * Retorna a URL completa da imagem do produto.
     *
     * @return string
     */
    public function getImageUrlAttribute(): string
    {
        if ( empty( $this->image ) ) {
            return asset( 'images/products/default.jpg' );
        }

        // Se a imagem já for uma URL completa, retorna como está
        if ( filter_var( $this->image, FILTER_VALIDATE_URL ) ) {
            return $this->image;
        }

        // Caso contrário, assume que é um caminho relativo
        return asset( 'storage/' . $this->image );
    }

    // ==================== MÉTODOS AUXILIARES ====================

    /**
     * Retorna a quantidade total em estoque do produto.
     *
     * @return float
     */
    public function getTotalStockAttribute(): float
    {
        return $this->productInventory()->sum( 'quantity' );
    }

    /**
     * Retorna o valor total do produto em estoque.
     *
     * @return float
     */
    public function getTotalStockValueAttribute(): float
    {
        return $this->price * $this->total_stock;
    }

    /**
     * Verifica se o produto está em baixa no estoque.
     *
     * @param  float  $threshold
     * @return bool
     */
    public function isLowStock( $threshold = 10 ): bool
    {
        return $this->total_stock <= $threshold;
    }

}
