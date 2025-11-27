<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model
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

        // Auto-calcular totais quando salvar
        static::saving(function ($item) {
            $item->calculateTotals();
        });
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'budget_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'budget_id',
        'budget_item_category_id',
        'title',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'discount_percentage',
        'tax_percentage',
        'total_price',
        'net_total',
        'order_index',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'budget_id' => 'integer',
        'budget_item_category_id' => 'integer',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'total_price' => 'decimal:2',
        'net_total' => 'decimal:2',
        'order_index' => 'integer',
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo BudgetItem.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'budget_id' => 'required|integer|exists:budgets,id',
            'budget_item_category_id' => 'nullable|integer|exists:budget_item_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'quantity' => 'required|numeric|min:0.01|max:999999.99',
            'unit' => 'required|string|max:20',
            'unit_price' => 'required|numeric|min:0|max:999999.99',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'total_price' => 'required|numeric|min:0|max:999999.99',
            'net_total' => 'required|numeric|min:0|max:999999.99',
            'order_index' => 'required|integer|min:0',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Regras de validação para criação.
     */
    public static function createRules(): array
    {
        return self::businessRules();
    }

    /**
     * Regras de validação para atualização.
     */
    public static function updateRules(int $itemId): array
    {
        return self::businessRules();
    }

    /**
     * Get the tenant that owns the BudgetItem.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the budget that owns the BudgetItem.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the budget item category that owns the BudgetItem.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetItemCategory::class, 'budget_item_category_id');
    }

    /**
     * Calcula os totais do item.
     */
    public function calculateTotals(): void
    {
        // Total bruto do item
        $this->total_price = $this->quantity * $this->unit_price;

        // Aplicar desconto
        $discountAmount = $this->total_price * ($this->discount_percentage / 100);
        $subtotalAfterDiscount = $this->total_price - $discountAmount;

        // Aplicar impostos
        $taxAmount = $subtotalAfterDiscount * ($this->tax_percentage / 100);
        $this->net_total = $subtotalAfterDiscount + $taxAmount;
    }

    /**
     * Verifica se o item pode ser editado.
     */
    public function canBeEdited(): bool
    {
        return $this->budget->canBeEdited();
    }

    /**
     * Verifica se o item pode ser removido.
     */
    public function canBeRemoved(): bool
    {
        return $this->budget->canBeEdited();
    }

    /**
     * Atualiza a ordem do item.
     */
    public function updateOrder(int $newOrder): bool
    {
        $this->order_index = $newOrder;

        return $this->save();
    }

    /**
     * Duplica o item.
     */
    public function duplicate(): BudgetItem
    {
        $newItem = $this->replicate();
        $newItem->title = 'Cópia de '.$this->title;
        $newItem->save();

        return $newItem;
    }

    /**
     * Obtém o valor formatado para exibição.
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_price, 2, ',', '.');
    }

    /**
     * Obtém o valor líquido formatado para exibição.
     */
    public function getFormattedNetTotalAttribute(): string
    {
        return number_format($this->net_total, 2, ',', '.');
    }

    /**
     * Obtém o preço unitário formatado para exibição.
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2, ',', '.');
    }

    /**
     * Obtém a margem de lucro do item.
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->unit_price <= 0) {
            return 0;
        }

        // Se houver metadata com custo, calcular margem
        $cost = $this->metadata['cost'] ?? 0;
        if ($cost > 0) {
            return (($this->unit_price - $cost) / $this->unit_price) * 100;
        }

        return 0;
    }

    /**
     * Scope para ordenar por ordem_index.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    /**
     * Scope para itens de um orçamento específico.
     */
    public function scopeForBudget($query, int $budgetId)
    {
        return $query->where('budget_id', $budgetId);
    }

    /**
     * Scope para itens por categoria.
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('budget_item_category_id', $categoryId);
    }

    /**
     * Scope para itens com impostos.
     */
    public function scopeWithTax($query)
    {
        return $query->where('tax_percentage', '>', 0);
    }

    /**
     * Scope para itens com desconto.
     */
    public function scopeWithDiscount($query)
    {
        return $query->where('discount_percentage', '>', 0);
    }
}
