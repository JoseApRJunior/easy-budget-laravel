<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use App\DTOs\AbstractDTO;
use Illuminate\Http\UploadedFile;

readonly class ProviderUpdateDTO extends AbstractDTO
{
    public function __construct(
        // User
        public ?string $email = null,
        public ?string $person_type = null,
        public ?UploadedFile $logo = null,

        // CommonData
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $cpf = null,
        public ?string $birth_date = null,
        public ?string $company_name = null,
        public ?string $cnpj = null,
        public ?string $description = null,
        public ?int $area_of_activity_id = null,
        public ?int $profession_id = null,

        // Contact
        public ?string $email_personal = null,
        public ?string $phone_personal = null,
        public ?string $email_business = null,
        public ?string $phone_business = null,
        public ?string $website = null,

        // Address
        public ?string $address = null,
        public ?string $address_number = null,
        public ?string $neighborhood = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $cep = null,

        // BusinessData
        public ?string $fantasy_name = null,
        public ?string $state_registration = null,
        public ?string $municipal_registration = null,
        public ?string $founding_date = null,
        public ?string $industry = null,
        public ?string $company_size = null,
        public ?string $notes = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            email: $data['email'] ?? null,
            person_type: $data['person_type'] ?? null,
            logo: $data['logo'] ?? null,
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            cpf: $data['cpf'] ?? null,
            birth_date: $data['birth_date'] ?? null,
            company_name: $data['company_name'] ?? null,
            cnpj: $data['cnpj'] ?? null,
            description: $data['description'] ?? null,
            area_of_activity_id: isset($data['area_of_activity_id']) ? (int) $data['area_of_activity_id'] : null,
            profession_id: isset($data['profession_id']) ? (int) $data['profession_id'] : null,
            email_personal: $data['email_personal'] ?? null,
            phone_personal: $data['phone_personal'] ?? null,
            email_business: $data['email_business'] ?? null,
            phone_business: $data['phone_business'] ?? null,
            website: $data['website'] ?? null,
            address: $data['address'] ?? null,
            address_number: $data['address_number'] ?? null,
            neighborhood: $data['neighborhood'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            cep: $data['cep'] ?? null,
            fantasy_name: $data['fantasy_name'] ?? null,
            state_registration: $data['state_registration'] ?? null,
            municipal_registration: $data['municipal_registration'] ?? null,
            founding_date: $data['founding_date'] ?? null,
            industry: $data['industry'] ?? null,
            company_size: $data['company_size'] ?? null,
            notes: $data['notes'] ?? null
        );
    }
}
