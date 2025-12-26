<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

use App\DTOs\AbstractDTO;

readonly class BudgetShareDTO extends AbstractDTO
{
    public function __construct(
        public int $budget_id,
        public string $share_token,
        public ?int $tenant_id = null,
        public ?string $recipient_email = null,
        public ?string $recipient_name = null,
        public ?string $message = null,
        public ?array $permissions = null,
        public ?string $expires_at = null,
        public bool $is_active = true,
        public string $status = 'active',
        public int $access_count = 0,
        public ?string $last_accessed_at = null,
        public ?string $rejected_at = null,
    ) {}

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenant_id ?? null,
            'budget_id' => $this->budget_id,
            'share_token' => $this->share_token,
            'recipient_email' => $this->recipient_email,
            'recipient_name' => $this->recipient_name,
            'message' => $this->message,
            'permissions' => $this->permissions,
            'expires_at' => $this->expires_at,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'access_count' => $this->access_count,
            'last_accessed_at' => $this->last_accessed_at,
            'rejected_at' => $this->rejected_at,
        ];
    }

    public static function fromArray(array $data): static
    {
        // @phpstan-ignore-next-line
        return new static(
            budget_id: (int) $data['budget_id'],
            share_token: $data['share_token'],
            tenant_id: $data['tenant_id'] ?? null,
            recipient_email: $data['recipient_email'] ?? null,
            recipient_name: $data['recipient_name'] ?? null,
            message: $data['message'] ?? null,
            permissions: $data['permissions'] ?? null,
            expires_at: $data['expires_at'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
            status: $data['status'] ?? 'active',
            access_count: (int) ($data['access_count'] ?? 0),
            last_accessed_at: $data['last_accessed_at'] ?? null,
            rejected_at: $data['rejected_at'] ?? null,
        );
    }
}
