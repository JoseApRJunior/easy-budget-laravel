<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Models\Budget;
use App\Models\BudgetCalculationSettings;
use App\Models\ServiceItem;
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
    public function calculateTotals(Budget $budget): array
    {
        $settings = BudgetCalculationSettings::getForTenant($budget->tenant_id);

        $subtotal = 0;
        $discountTotal = 0;
        $taxesTotal = 0;
        $itemsCount = 0;

        foreach ($budget->services as $service) {
            foreach ($service->serviceItems as $item) {
                // No novo modelo, unit_price é unit_value e total_price/net_total são total
                $itemTotal = $item->total;

                $subtotal += $itemTotal;
                $itemsCount++;
            }
        }

        // Aplicar desconto global
        $globalDiscount = 0;
        if ($settings['apply_global_discount'] && $budget->global_discount_percentage > 0) {
            $globalDiscount = $subtotal * ($budget->global_discount_percentage / 100);
            $subtotal -= $globalDiscount;
            $discountTotal += $globalDiscount;
        }

        // Cálculo do total final
        $grandTotal = $subtotal + $taxesTotal - $discountTotal;

        // Arredondar se necessário
        if ($settings['round_calculations']) {
            $subtotal = $this->roundValue($subtotal, $settings);
            $discountTotal = $this->roundValue($discountTotal, $settings);
            $taxesTotal = $this->roundValue($taxesTotal, $settings);
            $grandTotal = $this->roundValue($grandTotal, $settings);
        }

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'taxes_total' => $taxesTotal,
            'grand_total' => $grandTotal,
            'items_count' => $itemsCount,
        ];
    }

    /**
     * Arredonda valor conforme configurações.
     */
    private function roundValue(float $value, array $settings): float
    {
        $decimalPlaces = $settings['decimal_places'] ?? 2;

        return round($value, $decimalPlaces);
    }

    /**
     * Recalcula todos os itens (serviços) de um orçamento.
     */
    public function recalculateServiceItems(Budget $budget): ServiceResult
    {
        try {
            DB::beginTransaction();

            $totals = $this->calculateTotals($budget);

            // Atualizar totais do orçamento
            $budget->update([
                'subtotal' => $totals['subtotal'],
                'total' => $totals['grand_total'],
                'updated_at' => now(),
            ]);

            DB::commit();

            return ServiceResult::success($totals, 'Cálculos atualizados com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();

            return ServiceResult::error(
                'Erro ao recalcular itens do serviço: '.$e->getMessage()
            );
        }
    }

    /**
     * Calcula margem de lucro de um item.
     */
    public function calculateProfitMargin(ServiceItem $item): float
    {
        $settings = BudgetCalculationSettings::getForTenant($item->tenant_id);

        if (! $settings['show_profit_margin']) {
            return 0;
        }

        // Buscar custo do produto associado
        $cost = $item->product->cost_price ?? 0;

        if ($cost <= 0 || $item->unit_value <= 0) {
            return 0;
        }

        return (($item->unit_value - $cost) / $item->unit_value) * 100;
    }

    /**
     * Calcula estatísticas de um orçamento.
     */
    public function calculateBudgetStats(Budget $budget): array
    {
        $items = collect();
        foreach ($budget->services as $service) {
            foreach ($service->serviceItems as $item) {
                $items->push($item);
            }
        }

        $totals = $this->calculateTotals($budget);

        $stats = [
            'totals' => $totals,
            'items' => [
                'count' => $items->count(),
                'with_discount' => 0, // Descontos agora são por serviço/item totalizado
                'with_tax' => 0,
                'categories' => $budget->services->groupBy('category_id')->count(),
            ],
            'averages' => [
                'item_price' => $items->count() > 0 ? $items->avg('unit_value') : 0,
                'discount_percentage' => 0,
                'tax_percentage' => 0,
            ],
        ];

        return $stats;
    }

    /**
     * Valida cálculos de um orçamento.
     */
    public function validateBudgetCalculations(Budget $budget): ServiceResult
    {
        $errors = [];
        $warnings = [];

        // Verificar se há itens sem preço
        $itemsWithoutPrice = collect();
        foreach ($budget->services as $service) {
            foreach ($service->serviceItems as $item) {
                if ($item->unit_value <= 0) {
                    $itemsWithoutPrice->push($item);
                }
            }
        }

        if ($itemsWithoutPrice->count() > 0) {
            $errors[] = 'Existem itens sem preço definido.';
        }

        // Verificar se há itens com quantidade zero ou negativa
        $invalidQuantity = collect();
        foreach ($budget->services as $service) {
            foreach ($service->serviceItems as $item) {
                if ($item->quantity <= 0) {
                    $invalidQuantity->push($item);
                }
            }
        }

        if ($invalidQuantity->count() > 0) {
            $errors[] = 'Existem itens com quantidade inválida.';
        }

        // Avisar sobre itens sem produto
        $itemsWithoutProduct = collect();
        foreach ($budget->services as $service) {
            foreach ($service->serviceItems as $item) {
                if (empty($item->product_id)) {
                    $itemsWithoutProduct->push($item);
                }
            }
        }

        if ($itemsWithoutProduct->count() > 0) {
            $warnings[] = 'Existem itens sem produto definido.';
        }

        // Verificar se o total é muito baixo
        $totals = $this->calculateTotals($budget);
        if ($totals['grand_total'] <= 0) {
            $errors[] = 'O total do orçamento é zero ou negativo.';
        }

        if (! empty($errors)) {
            return ServiceResult::error('Erros de validação: '.implode(' ', $errors));
        }

        $result = [
            'valid' => true,
            'warnings' => $warnings,
            'totals' => $totals,
        ];

        return ServiceResult::success($result, 'Validação concluída.');
    }

    /**
     * Aplica configurações padrão de cálculo.
     */
    public function applyDefaultSettings(int $tenantId): ServiceResult
    {
        try {
            $defaultSettings = BudgetCalculationSettings::getDefaultSettings();

            BudgetCalculationSettings::updateForTenant($tenantId, $defaultSettings);

            return ServiceResult::success($defaultSettings, 'Configurações padrão aplicadas.');

        } catch (\Exception $e) {
            return ServiceResult::error(
                'Erro ao aplicar configurações padrão: '.$e->getMessage()
            );
        }
    }

    /**
     * Obtém configurações de cálculo para um tenant.
     */
    public function getCalculationSettings(int $tenantId): array
    {
        return BudgetCalculationSettings::getForTenant($tenantId);
    }

    /**
     * Atualiza configurações de cálculo para um tenant.
     */
    public function updateCalculationSettings(int $tenantId, array $settings): ServiceResult
    {
        try {
            $updatedSettings = BudgetCalculationSettings::updateForTenant($tenantId, $settings);

            return ServiceResult::success($updatedSettings, 'Configurações atualizadas.');

        } catch (\Exception $e) {
            return ServiceResult::error(
                'Erro ao atualizar configurações: '.$e->getMessage()
            );
        }
    }
}
