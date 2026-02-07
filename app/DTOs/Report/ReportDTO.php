<?php

declare(strict_types=1);

namespace App\DTOs\Report;

use App\DTOs\AbstractDTO;

readonly class ReportDTO extends AbstractDTO
{
    public function __construct(
        public string $type,
        public ?string $description = null,
        public string $format = 'pdf',
        public ?array $filters = null,
        public ?string $status = 'pending',
        public ?string $file_name = null,
        public ?string $file_path = null,
        public ?int $size = null,
        public ?string $error_message = null,
        public ?string $generated_at = null,
        public ?int $user_id = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            type: $data['type'] ?? '',
            description: $data['description'] ?? null,
            format: $data['format'] ?? 'pdf',
            filters: $data['filters'] ?? null,
            status: $data['status'] ?? 'pending',
            file_name: $data['file_name'] ?? null,
            file_path: $data['file_path'] ?? null,
            size: isset($data['size']) ? (int) $data['size'] : null,
            error_message: $data['error_message'] ?? null,
            generated_at: $data['generated_at'] ?? null,
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }
}
