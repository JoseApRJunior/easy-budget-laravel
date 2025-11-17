<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetCalculationSettings extends Model
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
    protected $table = 'budget_calculation_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'auto_calculate',
        'apply_global_discount',
        'default_global_discount',
        'round_calculations',
        'decimal_places',
        'show_item_discount',
        'show_item_tax',
        'show_profit_margin',
        'tax_settings',
        'custom_fields',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id'               => 'integer',
        'auto_calculate'          => 'boolean',
        'apply_global_discount'   => 'boolean',
        'default_global_discount' => 'decimal:2',
        'round_calculations'      => 'boolean',
        'decimal_places'          => 'integer',
        'show_item_discount'      => 'boolean',
        'show_item_tax'           => 'boolean',
        'show_profit_margin'      => 'boolean',
        'tax_settings'            => 'array',
        'custom_fields'           => 'array',
        'created_at'              => 'immutable_datetime',
        'updated_at'              => 'datetime',
    ];

    /**
     * Get the tenant that owns the BudgetCalculationSettings.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Obtém configurações padrão.
     */
    public static function getDefaultSettings(): array
    {
        return [
            'auto_calculate'          => true,
            'apply_global_discount'   => true,
            'default_global_discount' => 0.00,
            'round_calculations'      => true,
            'decimal_places'          => 2,
            'show_item_discount'      => true,
            'show_item_tax'           => true,
            'show_profit_margin'      => true,
            'tax_settings'            => [
                'default_tax'   => 0,
                'compound_tax'  => false,
                'tax_inclusive' => false,
            ],
            'custom_fields'           => [
                'show_cost'   => false,
                'show_markup' => false,
                'show_notes'  => true,
            ],
        ];
    }

    /**
     * Obtém configurações para um tenant específico.
     */
    public static function getForTenant( int $tenantId ): array
    {
        $settings = static::where( 'tenant_id', $tenantId )->first();

        if ( !$settings ) {
            return static::getDefaultSettings();
        }

        return [
            'auto_calculate'          => $settings->auto_calculate,
            'apply_global_discount'   => $settings->apply_global_discount,
            'default_global_discount' => $settings->default_global_discount,
            'round_calculations'      => $settings->round_calculations,
            'decimal_places'          => $settings->decimal_places,
            'show_item_discount'      => $settings->show_item_discount,
            'show_item_tax'           => $settings->show_item_tax,
            'show_profit_margin'      => $settings->show_profit_margin,
            'tax_settings'            => $settings->tax_settings ?? [],
            'custom_fields'           => $settings->custom_fields ?? [],
        ];
    }

    /**
     * Atualiza configurações para um tenant.
     */
    public static function updateForTenant( int $tenantId, array $settings ): self
    {
        return static::updateOrCreate(
            [ 'tenant_id' => $tenantId ],
            $settings,
        );
    }

    /**
     * Verifica se deve calcular automaticamente.
     */
    public function shouldAutoCalculate(): bool
    {
        return $this->auto_calculate;
    }

    /**
     * Verifica se deve aplicar desconto global.
     */
    public function shouldApplyGlobalDiscount(): bool
    {
        return $this->apply_global_discount;
    }

    /**
     * Obtém número de casas decimais.
     */
    public function getDecimalPlaces(): int
    {
        return $this->decimal_places;
    }

    /**
     * Verifica se deve arredondar cálculos.
     */
    public function shouldRoundCalculations(): bool
    {
        return $this->round_calculations;
    }

    /**
     * Obtém configurações de impostos.
     */
    public function getTaxSettings(): array
    {
        return $this->tax_settings ?? [];
    }

    /**
     * Obtém campos personalizados.
     */
    public function getCustomFields(): array
    {
        return $this->custom_fields ?? [];
    }

    /**
     * Verifica se deve mostrar desconto do item.
     */
    public function shouldShowItemDiscount(): bool
    {
        return $this->show_item_discount;
    }

    /**
     * Verifica se deve mostrar imposto do item.
     */
    public function shouldShowItemTax(): bool
    {
        return $this->show_item_tax;
    }

    /**
     * Verifica se deve mostrar margem de lucro.
     */
    public function shouldShowProfitMargin(): bool
    {
        return $this->show_profit_margin;
    }

}
