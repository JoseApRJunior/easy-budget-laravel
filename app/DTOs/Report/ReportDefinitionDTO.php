<?php

declare(strict_types=1);

namespace App\DTOs\Report;

use App\DTOs\AbstractDTO;

readonly class ReportDefinitionDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $data_source,
        public ?string $description = null,
        public array $parameters_schema = [],
        public array $columns_definition = [],
        public ?string $chart_type = null,
        public array $chart_config = [],
        public bool $is_active = true,
        public bool $is_system = false,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            data_source: $data['data_source'],
            description: $data['description'] ?? null,
            parameters_schema: is_string($data['parameters_schema'] ?? []) ? json_decode($data['parameters_schema'], true) : ($data['parameters_schema'] ?? []),
            columns_definition: is_string($data['columns_definition'] ?? []) ? json_decode($data['columns_definition'], true) : ($data['columns_definition'] ?? []),
            chart_type: $data['chart_type'] ?? null,
            chart_config: is_string($data['chart_config'] ?? []) ? json_decode($data['chart_config'], true) : ($data['chart_config'] ?? []),
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
            is_system: isset($data['is_system']) ? (bool) $data['is_system'] : false,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'data_source' => $this->data_source,
            'description' => $this->description,
            'parameters_schema' => $this->parameters_schema,
            'columns_definition' => $this->columns_definition,
            'chart_type' => $this->chart_type,
            'chart_config' => $this->chart_config,
            'is_active' => $this->is_active,
            'is_system' => $this->is_system,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
