<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

use App\DTOs\AbstractDTO;

readonly class BudgetCalculationSettingsDTO extends AbstractDTO
{
    public function __construct(
        public bool $calculate_tax,
        public float $default_tax_percentage,
        public bool $calculate_discount,
        public bool $item_level_discount,
        public string $rounding_method,
        public int $rounding_precision,
        public string $currency_symbol,
        public string $currency_code,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            calculate_tax: isset($data['calculate_tax']) ? (bool) $data['calculate_tax'] : false,
            default_tax_percentage: isset($data['default_tax_percentage']) ? (float) $data['default_tax_percentage'] : 0.0,
            calculate_discount: isset($data['calculate_discount']) ? (bool) $data['calculate_discount'] : false,
            item_level_discount: isset($data['item_level_discount']) ? (bool) $data['item_level_discount'] : false,
            rounding_method: $data['rounding_method'] ?? 'round',
            rounding_precision: isset($data['rounding_precision']) ? (int) $data['rounding_precision'] : 2,
            currency_symbol: $data['currency_symbol'] ?? 'R$',
            currency_code: $data['currency_code'] ?? 'BRL',
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'calculate_tax' => $this->calculate_tax,
            'default_tax_percentage' => $this->default_tax_percentage,
            'calculate_discount' => $this->calculate_discount,
            'item_level_discount' => $this->item_level_discount,
            'rounding_method' => $this->rounding_method,
            'rounding_precision' => $this->rounding_precision,
            'currency_symbol' => $this->currency_symbol,
            'currency_code' => $this->currency_code,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
