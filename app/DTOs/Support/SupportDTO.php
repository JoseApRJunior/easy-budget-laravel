<?php

declare(strict_types=1);

namespace App\DTOs\Support;

use App\DTOs\AbstractDTO;

readonly class SupportDTO extends AbstractDTO
{
    public function __construct(
        public string $subject,
        public string $message,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $email = null,
        public string $status = 'ABERTO',
        public string $priority = 'MEDIUM',
        public ?string $category = null,
        public ?array $attachments = null,
        public ?int $assigned_to = null,
        public ?int $user_id = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            subject: $data['subject'] ?? '',
            message: $data['message'] ?? '',
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            email: $data['email'] ?? null,
            status: $data['status'] ?? 'ABERTO',
            priority: $data['priority'] ?? 'MEDIUM',
            category: $data['category'] ?? null,
            attachments: $data['attachments'] ?? null,
            assigned_to: isset($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }
}
