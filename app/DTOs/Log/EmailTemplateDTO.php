<?php

declare(strict_types=1);

namespace App\DTOs\Log;

use App\DTOs\AbstractDTO;

readonly class EmailTemplateDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $category,
        public string $subject,
        public string $html_content,
        public ?string $text_content = null,
        public ?array $variables = null,
        public bool $is_active = true,
        public bool $is_system = false,
        public int $sort_order = 0,
        public ?array $metadata = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            category: $data['category'],
            subject: $data['subject'],
            html_content: $data['html_content'],
            text_content: $data['text_content'] ?? null,
            variables: is_string($data['variables'] ?? []) ? json_decode($data['variables'], true) : ($data['variables'] ?? []),
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
            is_system: isset($data['is_system']) ? (bool) $data['is_system'] : false,
            sort_order: isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            metadata: is_string($data['metadata'] ?? []) ? json_decode($data['metadata'], true) : ($data['metadata'] ?? []),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'name'         => $this->name,
            'slug'         => $this->slug,
            'category'     => $this->category,
            'subject'      => $this->subject,
            'html_content' => $this->html_content,
            'text_content' => $this->text_content,
            'variables'    => $this->variables,
            'is_active'    => $this->is_active,
            'is_system'    => $this->is_system,
            'sort_order'   => $this->sort_order,
            'metadata'     => $this->metadata,
            'tenant_id'    => $this->tenant_id,
        ];
    }
}
