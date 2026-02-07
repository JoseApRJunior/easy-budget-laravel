<?php

declare(strict_types=1);

namespace App\DTOs\Common;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;

/**
 * DTO para transferência de dados empresariais (BusinessData).
 */
readonly class BusinessDataDTO extends AbstractDTO
{
    public function __construct(
        public ?int $tenant_id = null,
        public ?int $customer_id = null,
        public ?int $provider_id = null,
        public ?string $fantasy_name = null,
        public ?string $state_registration = null,
        public ?string $municipal_registration = null,
        public ?string $founding_date = null,
        public ?string $industry = null,
        public ?string $company_size = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            customer_id: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            provider_id: isset($data['provider_id']) ? (int) $data['provider_id'] : null,
            fantasy_name: $data['fantasy_name'] ?? null,
            state_registration: $data['state_registration'] ?? null,
            municipal_registration: $data['municipal_registration'] ?? null,
            founding_date: DateHelper::parseDate($data['founding_date'] ?? null),
            industry: $data['industry'] ?? null,
            company_size: $data['company_size'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
