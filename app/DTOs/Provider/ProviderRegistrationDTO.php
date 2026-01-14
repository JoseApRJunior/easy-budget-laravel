<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use App\DTOs\AbstractDTO;

readonly class ProviderRegistrationDTO extends AbstractDTO
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $email,
        public ?string $password = null,
        public bool $terms_accepted = false,
        public ?string $phone = null,
        public ?string $phone_personal = null,
        public ?string $email_personal = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            first_name: $data['first_name'] ?? '',
            last_name: $data['last_name'] ?? '',
            email: $data['email'] ?? '',
            password: $data['password'] ?? null,
            terms_accepted: (bool) ($data['terms_accepted'] ?? false),
            phone: $data['phone'] ?? null,
            phone_personal: $data['phone_personal'] ?? null,
            email_personal: $data['email_personal'] ?? null
        );
    }
}
