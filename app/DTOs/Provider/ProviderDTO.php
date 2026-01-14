<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use App\DTOs\AbstractDTO;
use App\DTOs\Common\AddressDTO;
use App\DTOs\Common\CommonDataDTO;
use App\DTOs\Common\ContactDTO;
use App\DTOs\User\UserDTO;

readonly class ProviderDTO extends AbstractDTO
{
    public function __construct(
        public ?int $user_id = null,
        public bool $terms_accepted = false,
        public ?UserDTO $user = null,
        public ?CommonDataDTO $common_data = null,
        public ?AddressDTO $address = null,
        public ?ContactDTO $contact = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            terms_accepted: (bool) ($data['terms_accepted'] ?? false),
            user: isset($data['user']) ? UserDTO::fromRequest($data['user']) : (isset($data['name']) ? UserDTO::fromRequest($data) : null),
            common_data: isset($data['common_data']) ? CommonDataDTO::fromRequest($data['common_data']) : (isset($data['document']) ? CommonDataDTO::fromRequest($data) : null),
            address: isset($data['address']) ? AddressDTO::fromRequest($data['address']) : (isset($data['zip_code']) || isset($data['address']) ? AddressDTO::fromRequest($data) : null),
            contact: isset($data['contact']) ? ContactDTO::fromRequest($data['contact']) : (isset($data['phone']) ? ContactDTO::fromRequest($data) : null),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }
}
