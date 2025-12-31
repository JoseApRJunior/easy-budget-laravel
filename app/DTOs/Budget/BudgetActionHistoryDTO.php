<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;
use Carbon\Carbon;

readonly class BudgetActionHistoryDTO extends AbstractDTO
{
    public function __construct(
        public int $budget_id,
        public int $user_id,
        public string $action,
        public ?string $description = null,
        public ?array $changes = null,
        public ?Carbon $created_at = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            budget_id: (int) $data['budget_id'],
            user_id: (int) $data['user_id'],
            action: $data['action'],
            description: $data['description'] ?? null,
            changes: is_string($data['changes'] ?? []) ? json_decode($data['changes'], true) : ($data['changes'] ?? []),
            created_at: DateHelper::toCarbon($data['created_at'] ?? null) ?? now(),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'budget_id' => $this->budget_id,
            'user_id' => $this->user_id,
            'action' => $this->action,
            'description' => $this->description,
            'changes' => $this->changes,
            'created_at' => $this->created_at?->toDateTimeString(),
            'tenant_id' => $this->tenant_id,
        ];
    }
}
