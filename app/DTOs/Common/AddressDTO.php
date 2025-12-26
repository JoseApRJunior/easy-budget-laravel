<?php

declare(strict_types=1);

namespace App\DTOs\Common;

use App\DTOs\AbstractDTO;

readonly class AddressDTO extends AbstractDTO
{
    public function __construct(
        public ?string $address = null,
        public ?string $address_number = null,
        public ?string $neighborhood = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $cep = null,
        public ?int $customer_id = null,
        public ?int $provider_id = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            address: $data['address'] ?? null,
            address_number: $data['address_number'] ?? null,
            neighborhood: $data['neighborhood'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            cep: isset($data['cep']) ? preg_replace('/[^0-9]/', '', (string) $data['cep']) : null,
            customer_id: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            provider_id: isset($data['provider_id']) ? (int) $data['provider_id'] : null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'address' => $this->address,
            'address_number' => $this->address_number,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'cep' => $this->cep,
            'customer_id' => $this->customer_id,
            'provider_id' => $this->provider_id,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
