<?php

declare(strict_types=1);

namespace App\DTOs\Common;

use App\DTOs\AbstractDTO;

readonly class ContactDTO extends AbstractDTO
{
    public function __construct(
        public ?string $email_personal = null,
        public ?string $phone_personal = null,
        public ?string $email_business = null,
        public ?string $phone_business = null,
        public ?string $website = null,
        public ?int $customer_id = null,
        public ?int $provider_id = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            email_personal: $data['email_personal'] ?? null,
            phone_personal: $data['phone_personal'] ?? null,
            email_business: $data['email_business'] ?? null,
            phone_business: $data['phone_business'] ?? null,
            website: $data['website'] ?? null,
            customer_id: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            provider_id: isset($data['provider_id']) ? (int) $data['provider_id'] : null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'email_personal' => $this->email_personal,
            'phone_personal' => $this->phone_personal,
            'email_business' => $this->email_business,
            'phone_business' => $this->phone_business,
            'website'        => $this->website,
            'customer_id'    => $this->customer_id,
            'provider_id'    => $this->provider_id,
            'tenant_id'      => $this->tenant_id,
        ];
    }
}
