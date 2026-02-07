<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;
use Carbon\Carbon;

readonly class BudgetVersionDTO extends AbstractDTO
{
    public function __construct(
        public int $budget_id,
        public int $user_id,
        public string $version_number,
        public array $budget_data,
        public array $items_data,
        public float $version_total,
        public Carbon $version_date,
        public ?string $changes_description = null,
        public bool $is_current = false,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            budget_id: (int) $data['budget_id'],
            user_id: (int) $data['user_id'],
            version_number: $data['version_number'],
            budget_data: is_string($data['budget_data']) ? json_decode($data['budget_data'], true) : ($data['budget_data'] ?? []),
            items_data: is_string($data['items_data']) ? json_decode($data['items_data'], true) : ($data['items_data'] ?? []),
            version_total: (float) $data['version_total'],
            version_date: DateHelper::toCarbon($data['version_date'] ?? null) ?? now(),
            changes_description: $data['changes_description'] ?? null,
            is_current: isset($data['is_current']) ? (bool) $data['is_current'] : false,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'budget_id' => $this->budget_id,
            'user_id' => $this->user_id,
            'version_number' => $this->version_number,
            'budget_data' => $this->budget_data,
            'items_data' => $this->items_data,
            'version_total' => $this->version_total,
            'version_date' => $this->version_date->toDateTimeString(),
            'changes_description' => $this->changes_description,
            'is_current' => $this->is_current,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
