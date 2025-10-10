<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Models\Budget;
use App\Models\BudgetCalculationSettings;
use App\Models\BudgetItem;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;

class BudgetCalculationService
{
    private BudgetCalculationSettings $settings;

    public function __construct()
    {
        // Por enquanto usar configurações padrão
        // Depois pode ser injetado por tenant
    }

    /**
     * Calcula totais de um orçamento.
     */
    public function calculateTotals( Budget $budget ): array
    {
        $settings = BudgetCalculationSettings::getForTenant( $budget->tenant_id );

        $subtotal      = 0;
        $discountTotal = 0;
        $taxesTotal    = 0;
        $itemsCount    = 0;

        foreach ( $budget->items as $item ) {
            $itemTotal = $this->calculateItemTotal( $item, $settings );

            // Aplicar desconto do item
            if ( $item->discount_percentage > 0 ) {
                $itemDiscount  = $itemTotal * ( $item->discount_percentage / 100 );
                $itemTotal -= $itemDiscount;
                $discountTotal += $itemDiscount;
            }

            // Aplicar impostos do item
            if ( $item->tax_percentage > 0 ) {
                $itemTax    = $itemTotal * ( $item->tax_percentage / 100 );
                $itemTotal += $itemTax;
                $taxesTotal += $itemTax;
            }

            $subtotal += $itemTotal;
            $itemsCount++;

            // Atualizar total do item se necessário
            if ( $settings[ 'auto_calculate' ] ) {
                $item->update( [
                    'total_price' => $this->roundValue( $item->quantity * $item->unit_price, $settings ),
                    'net_total'   => $this->roundValue( $itemTotal, $settings ),
                ] );
            }
        }

        // Aplicar desconto global
        $globalDiscount = 0;
        if ( $settings[ 'apply_global_discount' ] && $budget->global_discount_percentage > 0 ) {
            $globalDiscount = $subtotal * ( $budget->global_discount_percentage / 100 );
            $subtotal -= $globalDiscount;
            $discountTotal += $globalDiscount;
        }

        // Cálculo do total final
        $grandTotal = $subtotal + $taxesTotal - $discountTotal;

        // Arredondar se necessário
        if ( $settings[ 'round_calculations' ] ) {
            $subtotal      = $this->roundValue( $subtotal, $settings );
            $discountTotal = $this->roundValue( $discountTotal, $settings );
            $taxesTotal    = $this->roundValue( $taxesTotal, $settings );
            $grandTotal    = $this->roundValue( $grandTotal, $settings );
        }

        return [
            'subtotal'       => $subtotal,
            'discount_total' => $discountTotal,
            'taxes_total'    => $taxesTotal,
            'grand_total'    => $grandTotal,
            'items_count'    => $itemsCount,
        ];
    }

    /**
     * Calcula o total de um item específico.
     */
    private function calculateItemTotal( BudgetItem $item, array $settings ): float
    {
        $total = $item->quantity * $item->unit_price;

        // Aplicar configurações de imposto
        $taxSettings = $settings[ 'tax_settings' ] ?? [];

        if ( isset( $taxSettings[ 'tax_inclusive' ] ) && $taxSettings[ 'tax_inclusive' ] ) {
            // Se o preço já inclui imposto, calcular o valor base
            if ( $item->tax_percentage > 0 ) {
                $total = $total / ( 1 + ( $item->tax_percentage / 100 ) );
            }
        }

        return $total;
    }

    /**
     * Arredonda valor conforme configurações.
     */
    private function roundValue( float $value, array $settings ): float
    {
        $decimalPlaces = $settings[ 'decimal_places' ] ?? 2;
        return round( $value, $decimalPlaces );
    }

    /**
     * Recalcula todos os itens de um orçamento.
     */
    public function recalculateBudgetItems( Budget $budget ): ServiceResult
    {
        try {
            DB::beginTransaction();

            $totals = $this->calculateTotals( $budget );

            // Atualizar totais do orçamento
            $budget->update( [
                'subtotal'   => $totals[ 'subtotal' ],
                'total'      => $totals[ 'grand_total' ],
                'updated_at' => now(),
            ] );

            DB::commit();

            return ServiceResult::success( $totals, 'Cálculos atualizados com sucesso.' );

        } catch ( \Exception $e ) {
            DB::rollBack();

            return ServiceResult::error(
                'Erro ao recalcular itens do orçamento: ' . $e->getMessage()
            );
        }
    }

    /**
     * Calcula margem de lucro de um item.
     */
    public function calculateProfitMargin( BudgetItem $item ): float
    {
        $settings = BudgetCalculationSettings::getForTenant( $item->tenant_id );

        if ( !$settings[ 'show_profit_margin' ] ) {
            return 0;
        }

        $cost = $item->metadata[ 'cost' ] ?? 0;
        if ( $cost <= 0 || $item->unit_price <= 0 ) {
            return 0;
        }

        return ( ( $item->unit_price - $cost ) / $item->unit_price ) * 100;
    }

    /**
     * Calcula estatísticas de um orçamento.
     */
    public function calculateBudgetStats( Budget $budget ): array
    {
        $items  = $budget->items;
        $totals = $this->calculateTotals( $budget );

        $stats = [
            'totals'   => $totals,
            'items'    => [
                'count'         => $items->count(),
                'with_discount' => $items->where( 'discount_percentage', '>', 0 )->count(),
                'with_tax'      => $items->where( 'tax_percentage', '>', 0 )->count(),
                'categories'    => $items->groupBy( 'budget_item_category_id' )->count(),
            ],
            'averages' => [
                'item_price'          => $items->count() > 0 ? $items->avg( 'unit_price' ) : 0,
                'discount_percentage' => $items->count() > 0 ? $items->avg( 'discount_percentage' ) : 0,
                'tax_percentage'      => $items->count() > 0 ? $items->avg( 'tax_percentage' ) : 0,
            ],
        ];

        return $stats;
    }

    /**
     * Valida cálculos de um orçamento.
     */
    public function validateBudgetCalculations( Budget $budget ): ServiceResult
    {
        $errors   = [];
        $warnings = [];

        // Verificar se há itens sem preço
        $itemsWithoutPrice = $budget->items->where( 'unit_price', '<=', 0 );
        if ( $itemsWithoutPrice->count() > 0 ) {
            $errors[] = 'Existem itens sem preço definido.';
        }

        // Verificar se há itens com quantidade zero ou negativa
        $invalidQuantity = $budget->items->where( 'quantity', '<=', 0 );
        if ( $invalidQuantity->count() > 0 ) {
            $errors[] = 'Existem itens com quantidade inválida.';
        }

        // Verificar descontos acima de 100%
        $highDiscounts = $budget->items->where( 'discount_percentage', '>', 100 );
        if ( $highDiscounts->count() > 0 ) {
            $errors[] = 'Existem itens com desconto acima de 100%.';
        }

        // Avisar sobre itens sem categoria
        $itemsWithoutCategory = $budget->items->whereNull( 'budget_item_category_id' );
        if ( $itemsWithoutCategory->count() > 0 ) {
            $warnings[] = 'Existem itens sem categoria definida.';
        }

        // Verificar se o total é muito baixo
        $totals = $this->calculateTotals( $budget );
        if ( $totals[ 'grand_total' ] <= 0 ) {
            $errors[] = 'O total do orçamento é zero ou negativo.';
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error( 'Erros de validação: ' . implode( ' ', $errors ) );
        }

        $result = [
            'valid'    => true,
            'warnings' => $warnings,
            'totals'   => $totals,
        ];

        return ServiceResult::success( $result, 'Validação concluída.' );
    }

    /**
     * Aplica configurações padrão de cálculo.
     */
    public function applyDefaultSettings( int $tenantId ): ServiceResult
    {
        try {
            $defaultSettings = BudgetCalculationSettings::getDefaultSettings();

            BudgetCalculationSettings::updateForTenant( $tenantId, $defaultSettings );

            return ServiceResult::success( $defaultSettings, 'Configurações padrão aplicadas.' );

        } catch ( \Exception $e ) {
            return ServiceResult::error(
                'Erro ao aplicar configurações padrão: ' . $e->getMessage()
            );
        }
    }

    /**
     * Obtém configurações de cálculo para um tenant.
     */
    public function getCalculationSettings( int $tenantId ): array
    {
        return BudgetCalculationSettings::getForTenant( $tenantId );
    }

    /**
     * Atualiza configurações de cálculo para um tenant.
     */
    public function updateCalculationSettings( int $tenantId, array $settings ): ServiceResult
    {
        try {
            $updatedSettings = BudgetCalculationSettings::updateForTenant( $tenantId, $settings );

            return ServiceResult::success( $updatedSettings, 'Configurações atualizadas.' );

        } catch ( \Exception $e ) {
            return ServiceResult::error(
                'Erro ao atualizar configurações: ' . $e->getMessage()
            );
        }
    }

}
