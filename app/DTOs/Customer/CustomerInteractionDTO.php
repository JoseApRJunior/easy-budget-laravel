<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;

/**
 * DTO para transferência de dados de Interação com Cliente.
 */
readonly class CustomerInteractionDTO extends AbstractDTO
{
    public function __construct(
        public string $type,
        public string $title,
        public ?string $description = null,
        public string $direction = 'outbound',
        public ?string $interaction_date = null,
        public ?int $duration_minutes = null,
        public ?string $outcome = null,
        public ?string $next_action = null,
        public ?string $next_action_date = null,
        public array $attachments = [],
        public array $metadata = [],
        public bool $notify_customer = false,
    ) {}

    /**
     * Cria uma instância de CustomerInteractionDTO a partir de um array de dados validados.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            type: $data['type'],
            title: $data['title'],
            description: $data['description'] ?? null,
            direction: $data['direction'] ?? 'outbound',
            interaction_date: DateHelper::parseDate($data['interaction_date'] ?? null) ?: now()->toDateTimeString(),
            duration_minutes: isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : null,
            outcome: $data['outcome'] ?? null,
            next_action: $data['next_action'] ?? null,
            next_action_date: DateHelper::parseDate($data['next_action_date'] ?? null),
            attachments: $data['attachments'] ?? [],
            metadata: $data['metadata'] ?? [],
            notify_customer: (bool) ($data['notify_customer'] ?? false),
        );
    }
}
