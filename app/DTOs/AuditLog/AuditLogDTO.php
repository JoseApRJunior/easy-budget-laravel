<?php

declare(strict_types=1);

namespace App\DTOs\AuditLog;

use App\DTOs\AbstractDTO;

readonly class AuditLogDTO extends AbstractDTO
{
    public function __construct(
        public string $action,
        public string $model_type,
        public ?int $model_id = null,
        public ?array $old_values = null,
        public ?array $new_values = null,
        public ?string $ip_address = null,
        public ?string $user_agent = null,
        public ?array $metadata = null,
        public ?string $description = null,
        public string $severity = 'info',
        public ?string $category = null,
        public bool $is_system_action = false,
        public ?int $user_id = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            action: $data['action'] ?? '',
            model_type: $data['model_type'] ?? '',
            model_id: isset($data['model_id']) ? (int) $data['model_id'] : null,
            old_values: $data['old_values'] ?? null,
            new_values: $data['new_values'] ?? null,
            ip_address: $data['ip_address'] ?? null,
            user_agent: $data['user_agent'] ?? null,
            metadata: $data['metadata'] ?? null,
            description: $data['description'] ?? null,
            severity: $data['severity'] ?? 'info',
            category: $data['category'] ?? null,
            is_system_action: (bool) ($data['is_system_action'] ?? false),
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }
}
