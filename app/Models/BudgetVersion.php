<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetVersion extends Model
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
    protected $table = 'budget_versions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'budget_id',
        'user_id',
        'version_number',
        'changes_description',
        'budget_data',
        'items_data',
        'version_total',
        'is_current',
        'version_date',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'budget_id' => 'integer',
        'user_id' => 'integer',
        'budget_data' => 'array',
        'items_data' => 'array',
        'version_total' => 'decimal:2',
        'is_current' => 'boolean',
        'version_date' => 'datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo BudgetVersion.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'budget_id' => 'required|integer|exists:budgets,id',
            'user_id' => 'required|integer|exists:users,id',
            'version_number' => 'required|string|max:20',
            'changes_description' => 'nullable|string|max:1000',
            'budget_data' => 'required|array',
            'items_data' => 'required|array',
            'version_total' => 'required|numeric|min:0|max:999999.99',
            'is_current' => 'required|boolean',
            'version_date' => 'required|date',
        ];
    }

    /**
     * Get the tenant that owns the BudgetVersion.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the budget that owns the BudgetVersion.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the user that owns the BudgetVersion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para versões atuais.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope para versões de um orçamento específico.
     */
    public function scopeForBudget($query, int $budgetId)
    {
        return $query->where('budget_id', $budgetId);
    }

    /**
     * Scope para ordenar por versão (descendente).
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('version_number', 'desc');
    }

    /**
     * Scope para ordenar por data de criação (descendente).
     */
    public function scopeRecentFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Obtém a versão anterior.
     */
    public function getPreviousVersion()
    {
        return static::where('budget_id', $this->budget_id)
            ->where('version_number', '<', $this->version_number)
            ->orderBy('version_number', 'desc')
            ->first();
    }

    /**
     * Obtém a próxima versão.
     */
    public function getNextVersion()
    {
        return static::where('budget_id', $this->budget_id)
            ->where('version_number', '>', $this->version_number)
            ->orderBy('version_number', 'asc')
            ->first();
    }

    /**
     * Compara esta versão com outra versão.
     */
    public function compareWith(BudgetVersion $otherVersion): array
    {
        if ($this->budget_id !== $otherVersion->budget_id) {
            return [];
        }

        $currentData = $this->budget_data;
        $otherData = $otherVersion->budget_data;

        $changes = [];

        // Comparar campos principais do orçamento
        $budgetFields = [
            'customer_id', 'budget_statuses_id', 'code', 'due_date',
            'discount', 'total', 'description', 'payment_terms',
        ];

        foreach ($budgetFields as $field) {
            if (isset($currentData[$field]) && isset($otherData[$field])) {
                if ($currentData[$field] !== $otherData[$field]) {
                    $changes['budget'][$field] = [
                        'from' => $otherData[$field],
                        'to' => $currentData[$field],
                    ];
                }
            }
        }

        // Comparar itens
        $currentItems = $this->items_data ?? [];
        $otherItems = $otherVersion->items_data ?? [];

        $changes['items'] = [
            'added' => $this->getAddedItems($otherItems, $currentItems),
            'removed' => $this->getRemovedItems($otherItems, $currentItems),
            'modified' => $this->getModifiedItems($otherItems, $currentItems),
        ];

        return $changes;
    }

    /**
     * Obtém itens adicionados entre duas versões.
     */
    private function getAddedItems(array $oldItems, array $newItems): array
    {
        $oldIds = array_column($oldItems, 'id');
        $added = [];

        foreach ($newItems as $newItem) {
            if (! in_array($newItem['id'] ?? null, $oldIds)) {
                $added[] = $newItem;
            }
        }

        return $added;
    }

    /**
     * Obtém itens removidos entre duas versões.
     */
    private function getRemovedItems(array $oldItems, array $newItems): array
    {
        $newIds = array_column($newItems, 'id');
        $removed = [];

        foreach ($oldItems as $oldItem) {
            if (! in_array($oldItem['id'] ?? null, $newIds)) {
                $removed[] = $oldItem;
            }
        }

        return $removed;
    }

    /**
     * Obtém itens modificados entre duas versões.
     */
    private function getModifiedItems(array $oldItems, array $newItems): array
    {
        $modified = [];
        $newItemsById = [];

        foreach ($newItems as $newItem) {
            if (isset($newItem['id'])) {
                $newItemsById[$newItem['id']] = $newItem;
            }
        }

        foreach ($oldItems as $oldItem) {
            if (isset($oldItem['id']) && isset($newItemsById[$oldItem['id']])) {
                $newItem = $newItemsById[$oldItem['id']];
                $itemChanges = [];

                $fieldsToCompare = ['title', 'description', 'quantity', 'unit_price', 'discount_percentage', 'tax_percentage'];

                foreach ($fieldsToCompare as $field) {
                    if (($oldItem[$field] ?? null) !== ($newItem[$field] ?? null)) {
                        $itemChanges[$field] = [
                            'from' => $oldItem[$field] ?? null,
                            'to' => $newItem[$field] ?? null,
                        ];
                    }
                }

                if (! empty($itemChanges)) {
                    $modified[] = [
                        'id' => $oldItem['id'],
                        'changes' => $itemChanges,
                    ];
                }
            }
        }

        return $modified;
    }

    /**
     * Marca esta versão como atual.
     */
    public function markAsCurrent(): bool
    {
        // Desmarcar outras versões como não atuais
        static::where('budget_id', $this->budget_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);

        // Marcar esta versão como atual
        $this->is_current = true;

        return $this->save();
    }

    /**
     * Obtém o tamanho da versão em bytes.
     */
    public function getSizeAttribute(): int
    {
        return strlen(json_encode($this->budget_data)) + strlen(json_encode($this->items_data));
    }

    /**
     * Obtém informações formatadas da versão.
     */
    public function getFormattedInfoAttribute(): array
    {
        return [
            'version' => $this->version_number,
            'date' => $this->version_date->format('d/m/Y H:i'),
            'user' => $this->user->name ?? 'Sistema',
            'total' => number_format($this->version_total, 2, ',', '.'),
            'is_current' => $this->is_current,
            'changes' => $this->changes_description,
        ];
    }
}
