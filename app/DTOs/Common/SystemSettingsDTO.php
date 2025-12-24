<?php

declare(strict_types=1);

namespace App\DTOs\Common;

use App\DTOs\AbstractDTO;

readonly class SystemSettingsDTO extends AbstractDTO
{
    public function __construct(
        public string $company_name,
        public string $contact_email,
        public ?string $phone = null,
        public ?string $website = null,
        public ?string $logo = null,
        public string $currency = 'BRL',
        public string $timezone = 'UTC',
        public string $language = 'pt-BR',
        public ?string $address_street = null,
        public ?string $address_number = null,
        public ?string $address_complement = null,
        public ?string $address_neighborhood = null,
        public ?string $address_city = null,
        public ?string $address_state = null,
        public ?string $address_zip_code = null,
        public ?string $address_country = null,
        public bool $maintenance_mode = false,
        public ?string $maintenance_message = null,
        public bool $registration_enabled = true,
        public bool $email_verification_required = false,
        public int $session_lifetime = 120,
        public int $max_login_attempts = 5,
        public int $lockout_duration = 60,
        public ?array $allowed_file_types = null,
        public int $max_file_size = 2048,
        public ?array $system_preferences = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            company_name: $data['company_name'],
            contact_email: $data['contact_email'],
            phone: $data['phone'] ?? null,
            website: $data['website'] ?? null,
            logo: $data['logo'] ?? null,
            currency: $data['currency'] ?? 'BRL',
            timezone: $data['timezone'] ?? 'UTC',
            language: $data['language'] ?? 'pt-BR',
            address_street: $data['address_street'] ?? null,
            address_number: $data['address_number'] ?? null,
            address_complement: $data['address_complement'] ?? null,
            address_neighborhood: $data['address_neighborhood'] ?? null,
            address_city: $data['address_city'] ?? null,
            address_state: $data['address_state'] ?? null,
            address_zip_code: $data['address_zip_code'] ?? null,
            address_country: $data['address_country'] ?? null,
            maintenance_mode: (bool) ($data['maintenance_mode'] ?? false),
            maintenance_message: $data['maintenance_message'] ?? null,
            registration_enabled: (bool) ($data['registration_enabled'] ?? true),
            email_verification_required: (bool) ($data['email_verification_required'] ?? false),
            session_lifetime: (int) ($data['session_lifetime'] ?? 120),
            max_login_attempts: (int) ($data['max_login_attempts'] ?? 5),
            lockout_duration: (int) ($data['lockout_duration'] ?? 60),
            allowed_file_types: $data['allowed_file_types'] ?? null,
            max_file_size: (int) ($data['max_file_size'] ?? 2048),
            system_preferences: $data['system_preferences'] ?? null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'company_name'                => $this->company_name,
            'contact_email'               => $this->contact_email,
            'phone'                       => $this->phone,
            'website'                     => $this->website,
            'logo'                        => $this->logo,
            'currency'                    => $this->currency,
            'timezone'                    => $this->timezone,
            'language'                    => $this->language,
            'address_street'              => $this->address_street,
            'address_number'              => $this->address_number,
            'address_complement'          => $this->address_complement,
            'address_neighborhood'        => $this->address_neighborhood,
            'address_city'                => $this->address_city,
            'address_state'               => $this->address_state,
            'address_zip_code'            => $this->address_zip_code,
            'address_country'             => $this->address_country,
            'maintenance_mode'            => $this->maintenance_mode,
            'maintenance_message'         => $this->maintenance_message,
            'registration_enabled'        => $this->registration_enabled,
            'email_verification_required' => $this->email_verification_required,
            'session_lifetime'            => $this->session_lifetime,
            'max_login_attempts'          => $this->max_login_attempts,
            'lockout_duration'            => $this->lockout_duration,
            'allowed_file_types'          => $this->allowed_file_types,
            'max_file_size'               => $this->max_file_size,
            'system_preferences'          => $this->system_preferences,
            'tenant_id'                   => $this->tenant_id,
        ];
    }
}
