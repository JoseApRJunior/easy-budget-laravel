<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetItemCategory extends Model
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
     */
    protected $table = 'budget_item_categories';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'default_tax_percentage',
        'is_active',
        'order_index',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id'              => 'integer',
        'default_tax_percentage' => 'decimal:2',
        'is_active'              => 'boolean',
        'order_index'            => 'integer',
        'created_at'             => 'immutable_datetime',
        'updated_at'             => 'datetime',
    ];

    /**
     * Regras de validação para o modelo BudgetItemCategory.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'              => 'required|integer|exists:tenants,id',
            'name'                   => 'required|string|max:100',
            'slug'                   => 'required|string|max:50|alpha_dash',
            'description'            => 'nullable|string|max:500',
            'color'                  => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i',
            'icon'                   => 'nullable|string|max:50',
            'default_tax_percentage' => 'required|numeric|min:0|max:100',
            'is_active'              => 'required|boolean',
            'order_index'            => 'required|integer|min:0',
        ];
    }

    /**
     * Regras de validação para criação.
     */
    public static function createRules(): array
    {
        $rules         = self::businessRules();
        $rules[ 'slug' ] = 'required|string|max:50|alpha_dash|unique:budget_item_categories,slug';

        return $rules;
    }

    /**
     * Regras de validação para atualização.
     */
    public static function updateRules( int $categoryId ): array
    {
        $rules         = self::businessRules();
        $rules[ 'slug' ] = 'required|string|max:50|alpha_dash|unique:budget_item_categories,slug,' . $categoryId;

        return $rules;
    }

    /**
     * Get the tenant that owns the BudgetItemCategory.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the budget items for the BudgetItemCategory.
     */
    public function budgetItems(): HasMany
    {
        return $this->hasMany( BudgetItem::class);
    }

    /**
     * Scope para categorias ativas.
     */
    public function scopeActive( $query )
    {
        return $query->where( 'is_active', true );
    }

    /**
     * Scope para ordenar por order_index.
     */
    public function scopeOrdered( $query )
    {
        return $query->orderBy( 'order_index' )->orderBy( 'name' );
    }

    /**
     * Scope para categorias por tenant.
     */
    public function scopeForTenant( $query, int $tenantId )
    {
        return $query->where( 'tenant_id', $tenantId );
    }

    /**
     * Verifica se a categoria pode ser excluída.
     */
    public function canBeDeleted(): bool
    {
        return $this->budgetItems()->count() === 0;
    }

    /**
     * Obtém categorias padrão do sistema.
     */
    public static function getDefaultCategories(): array
    {
        return [
            [
                'name'                   => 'Produtos',
                'slug'                   => 'produtos',
                'description'            => 'Produtos físicos ou digitais',
                'color'                  => '#3B82F6',
                'icon'                   => 'bi-box-seam',
                'default_tax_percentage' => 0,
                'order_index'            => 1,
            ],
            [
                'name'                   => 'Serviços',
                'slug'                   => 'servicos',
                'description'            => 'Serviços profissionais',
                'color'                  => '#10B981',
                'icon'                   => 'bi-tools',
                'default_tax_percentage' => 0,
                'order_index'            => 2,
            ],
            [
                'name'                   => 'Despesas',
                'slug'                   => 'despesas',
                'description'            => 'Despesas e custos operacionais',
                'color'                  => '#F59E0B',
                'icon'                   => 'bi-cash',
                'default_tax_percentage' => 0,
                'order_index'            => 3,
            ],
            [
                'name'                   => 'Taxas',
                'slug'                   => 'taxas',
                'description'            => 'Taxas e encargos',
                'color'                  => '#EF4444',
                'icon'                   => 'bi-receipt',
                'default_tax_percentage' => 0,
                'order_index'            => 4,
            ],
        ];
    }

    /**
     * Cria categorias padrão para um tenant.
     */
    public static function createDefaultCategories( int $tenantId ): void
    {
        foreach ( self::getDefaultCategories() as $category ) {
            self::create( array_merge( $category, [
                'tenant_id' => $tenantId,
                'is_active' => true,
            ] ) );
        }
    }

    /**
     * Atualiza a ordem da categoria.
     */
    public function updateOrder( int $newOrder ): bool
    {
        $this->order_index = $newOrder;
        return $this->save();
    }

    /**
     * Obtém a cor de fundo para CSS.
     */
    public function getBackgroundColorAttribute(): string
    {
        return $this->color ?: '#6B7280';
    }

    /**
     * Obtém a classe CSS para o ícone.
     */
    public function getIconClassAttribute(): string
    {
        return $this->icon ?: 'bi-circle';
    }

}
