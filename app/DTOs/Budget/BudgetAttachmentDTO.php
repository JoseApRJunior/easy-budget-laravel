<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

use App\DTOs\AbstractDTO;

readonly class BudgetAttachmentDTO extends AbstractDTO
{
    public function __construct(
        public int $budget_id,
        public string $filename,
        public string $original_filename,
        public string $mime_type,
        public int $size,
        public string $path,
        public ?string $description = null,
        public bool $is_public = false,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            budget_id: (int) $data['budget_id'],
            filename: $data['filename'],
            original_filename: $data['original_filename'],
            mime_type: $data['mime_type'],
            size: (int) $data['size'],
            path: $data['path'],
            description: $data['description'] ?? null,
            is_public: isset($data['is_public']) ? (bool) $data['is_public'] : false,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'budget_id'         => $this->budget_id,
            'filename'          => $this->filename,
            'original_filename' => $this->original_filename,
            'mime_type'         => $this->mime_type,
            'size'              => $this->size,
            'path'              => $this->path,
            'description'       => $this->description,
            'is_public'         => $this->is_public,
            'tenant_id'         => $this->tenant_id,
        ];
    }
}
