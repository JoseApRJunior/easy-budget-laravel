<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Plan para gerenciar planos de assinatura do sistema.
 * Representa os diferentes planos disponíveis para os tenants.
 */
class Plan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabela associada ao modelo Plan.
     */
    protected $table = 'plans';

    /**
     * Campos preenchíveis para o modelo Plan.
     */
    protected $fillable = [
        'name',
        'slug',
        'stripe_id',
        'description',
        'price',
        'status',
        'max_budgets',
        'max_clients',
        'features',
    ];

    /**
     * Casts para os campos do modelo Plan.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'boolean',
        'max_budgets' => 'integer',
        'max_clients' => 'integer',
        'features' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Plan.
     */
    public static function businessRules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'slug' => 'required|string|max:50|unique:plans,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'status' => 'boolean',
            'max_budgets' => 'required|integer|min:1',
            'max_clients' => 'required|integer|min:1',
            'features' => 'nullable|array',
        ];
    }

    /**
     * Scope para planos ativos.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope para planos ordenados por preço.
     */
    public function scopeOrderedByPrice($query)
    {
        return $query->orderBy('price');
    }

    /**
     * Verifica se o plano está ativo.
     */
    public function isActive(): bool
    {
        return $this->status;
    }

    /**
     * Verifica se o plano permite um determinado número de orçamentos.
     */
    public function allowsBudgets(int $count): bool
    {
        return $this->max_budgets >= $count;
    }

    /**
     * Verifica se o plano permite um determinado número de clientes.
     */
    public function allowsClients(int $count): bool
    {
        return $this->max_clients >= $count;
    }

    /**
     * Assinaturas deste plano.
     */
    public function planSubscriptions(): HasMany
    {
        return $this->hasMany(PlanSubscription::class);
    }
}
