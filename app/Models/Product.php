<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use Auditable, TenantScoped;
    use HasFactory;
    use SoftDeletes;

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
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * Relacionamentos carregados automaticamente
     *
     * @var array
     */
    protected $with = ['category'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'description',
        'sku',
        'price',
        'unit',
        'active',
        'image',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'category_id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'sku' => 'string',
        'price' => 'decimal:2',
        'unit' => 'string',
        'active' => 'boolean',
        'image' => 'string',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Product.
     *
     * @return array<string, string>
     */
    public static function businessRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:20',
            'active' => 'boolean',
            'image' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'tenant_id' => 'required|exists:tenants,id',
        ];
    }

    /**
     * Validação personalizada para sku único por tenant.
     * Esta validação deve ser usada no contexto de um request onde o tenant_id está disponível.
     */
    public static function validateUniqueSkuRule(?string $sku, ?int $excludeId = null): string
    {
        if (empty($sku)) {
            return 'nullable|string|max:255';
        }

        $rule = 'unique:products,sku';

        if ($excludeId) {
            $rule .= ','.$excludeId.',id';
        }

        return $rule.',tenant_id,'.request()->user()->tenant_id;
    }

    /**
     * Get the tenant that owns the Product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Tenant, \App\Models\Product>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the category that owns the Product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Category, \App\Models\Product>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Movimentações de inventário deste produto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\InventoryMovement>
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Controle de inventário do produto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ProductInventory>
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(ProductInventory::class);
    }

    /**
     * Itens de serviço que utilizam este produto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ServiceItem>
     */
    public function serviceItems(): HasMany
    {
        return $this->hasMany(ServiceItem::class);
    }

    // ==================== SCOPES ÚTEIS ====================

    /**
     * Scope para filtrar produtos ativos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para filtrar produtos por tenant específico.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $tenantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para filtrar produtos por faixa de preço.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $min
     * @param  float  $max
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Scope para buscar produtos por nome.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'LIKE', '%'.$name.'%');
    }

    /**
     * Scope para carregar produtos com dados de inventário.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithInventory($query)
    {
        return $query->with(['inventory']);
    }

    // ==================== MÉTODOS DE NEGÓCIO ====================

    /**
     * Verifica se o produto está disponível no inventário.
     */
    public function isAvailable(): bool
    {
        $totalQuantity = $this->inventory()
            ->sum('quantity');

        return $totalQuantity > 0;
    }

    /**
     * Alterna o status ativo/inativo do produto.
     */
    public function toggleActive(): bool
    {
        $this->active = ! $this->active;

        return $this->save();
    }

    // ==================== ACCESSORS ====================

    /**
     * Retorna o preço formatado como moeda (BRL).
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'R$ '.number_format((float) $this->price, 2, ',', '.');
    }

    /**
     * Retorna a URL completa da imagem do produto.
     */
    public function getImageUrlAttribute(): string
    {
        if (empty($this->image)) {
            // Usar a imagem "não encontrada" como fallback
            return asset('storage/img_not_found.png');
        }

        // Se a imagem já for uma URL completa, retorna como está
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        $p = ltrim((string) $this->image, '/');
        if (Str::startsWith($p, 'storage/')) {
            // Verificar se o arquivo existe antes de tentar acessá-lo
            $fullPath = storage_path('app/public/'.Str::after($p, 'storage/'));
            if (file_exists($fullPath)) {
                return asset($p);
            }
        } else {
            // Verificar se o arquivo existe antes de tentar acessá-lo
            $fullPath = storage_path('app/public/'.$p);
            if (file_exists($fullPath)) {
                return asset('storage/'.$p);
            }
        }

        // Se o arquivo não existir, retornar a imagem de fallback
        return asset('storage/img_not_found.png');
    }

    // ==================== MÉTODOS AUXILIARES ====================

    /**
     * Retorna a quantidade total em estoque do produto.
     */
    public function getTotalStockAttribute(): float
    {
        return $this->inventory()->sum('quantity');
    }

    /**
     * Retorna o valor total do produto em estoque.
     */
    public function getTotalStockValueAttribute(): float
    {
        return $this->price * $this->total_stock;
    }

    /**
     * Verifica se o produto está em baixa no estoque.
     *
     * @param  float  $threshold
     */
    public function isLowStock($threshold = 10): bool
    {
        return $this->total_stock <= $threshold;
    }

    /**
     * Accessor para compatibilidade - code como alias de sku
     */
    public function getCodeAttribute(): ?string
    {
        return $this->sku;
    }
}
