<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

use App\DTOs\AbstractDTO;

class BudgetShareDTO extends AbstractDTO
{
    public ?int $tenant_id;
    public int $budget_id;
    public string $share_token;
    public ?string $recipient_email;
    public ?string $recipient_name;
    public ?string $message;
    public ?array $permissions;
    public ?string $expires_at;
    public bool $is_active = true;
    public string $status = 'active';
    public int $access_count = 0;
    public ?string $last_accessed_at;
    public ?string $rejected_at;

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

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->tenant_id = $data['tenant_id'] ?? null;
        $dto->budget_id = (int) $data['budget_id'];
        $dto->share_token = $data['share_token'];
        $dto->recipient_email = $data['recipient_email'] ?? null;
        $dto->recipient_name = $data['recipient_name'] ?? null;
        $dto->message = $data['message'] ?? null;
        $dto->permissions = $data['permissions'] ?? null;
        $dto->expires_at = $data['expires_at'] ?? null;
        $dto->is_active = (bool) ($data['is_active'] ?? true);
        $dto->status = $data['status'] ?? 'active';
        $dto->access_count = (int) ($data['access_count'] ?? 0);
        $dto->last_accessed_at = $data['last_accessed_at'] ?? null;
        $dto->rejected_at = $data['rejected_at'] ?? null;

        return $dto;
    }
}
