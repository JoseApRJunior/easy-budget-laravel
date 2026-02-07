<?php

declare(strict_types=1);

namespace App\DTOs\Common;

use App\DTOs\AbstractDTO;
use Carbon\Carbon;

readonly class PdfDTO extends AbstractDTO
{
    public function __construct(
        public string $path,
        public string $type,
        public ?array $data = null,
        public ?array $metadata = null,
        public ?Carbon $generated_at = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            path: $data['path'],
            type: $data['type'],
            data: is_string($data['data'] ?? []) ? json_decode($data['data'], true) : ($data['data'] ?? []),
            metadata: is_string($data['metadata'] ?? []) ? json_decode($data['metadata'], true) : ($data['metadata'] ?? []),
            generated_at: isset($data['generated_at']) ? Carbon::parse($data['generated_at']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'type' => $this->type,
            'data' => $this->data,
            'metadata' => $this->metadata,
            'generated_at' => $this->generated_at?->toDateTimeString(),
        ];
    }
}
