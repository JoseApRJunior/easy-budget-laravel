<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;
use Carbon\Carbon;

readonly class BudgetTemplateDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $category,
        public array $template_data,
        public array $default_items,
        public int $user_id,
        public ?int $parent_template_id = null,
        public ?string $description = null,
        public ?array $variables = null,
        public ?float $estimated_hours = null,
        public bool $is_public = false,
        public bool $is_active = true,
        public int $usage_count = 0,
        public ?Carbon $last_used_at = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            category: $data['category'],
            template_data: is_string($data['template_data']) ? json_decode($data['template_data'], true) : ($data['template_data'] ?? []),
            default_items: is_string($data['default_items']) ? json_decode($data['default_items'], true) : ($data['default_items'] ?? []),
            user_id: (int) $data['user_id'],
            parent_template_id: isset($data['parent_template_id']) ? (int) $data['parent_template_id'] : null,
            description: $data['description'] ?? null,
            variables: isset($data['variables']) ? (is_string($data['variables']) ? json_decode($data['variables'], true) : $data['variables']) : [],
            estimated_hours: isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : null,
            is_public: isset($data['is_public']) ? (bool) $data['is_public'] : false,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
            usage_count: isset($data['usage_count']) ? (int) $data['usage_count'] : 0,
            last_used_at: DateHelper::toCarbon($data['last_used_at'] ?? null),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'template_data' => $this->template_data,
            'default_items' => $this->default_items,
            'user_id' => $this->user_id,
            'parent_template_id' => $this->parent_template_id,
            'description' => $this->description,
            'variables' => $this->variables,
            'estimated_hours' => $this->estimated_hours,
            'is_public' => $this->is_public,
            'is_active' => $this->is_active,
            'usage_count' => $this->usage_count,
            'last_used_at' => $this->last_used_at?->toDateTimeString(),
            'tenant_id' => $this->tenant_id,
        ];
    }
}
