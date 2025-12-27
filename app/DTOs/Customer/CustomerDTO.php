<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;
use App\Models\CommonData;

/**
 * DTO para transferência de dados de Cliente.
 * Gerencia a estrutura complexa de 5 tabelas (Customer, CommonData, Contact, Address, BusinessData).
 */
readonly class CustomerDTO extends AbstractDTO
{
    public function __construct(
        public string $type,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $birth_date = null,
        public ?string $cpf = null,
        public ?string $company_name = null,
        public ?string $cnpj = null,
        public ?string $description = null,
        public ?int $area_of_activity_id = null,
        public ?int $profession_id = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $email_personal = null,
        public ?string $phone_personal = null,
        public ?string $email_business = null,
        public ?string $phone_business = null,
        public ?string $website = null,
        public ?string $address = null,
        public ?string $address_number = null,
        public ?string $neighborhood = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $cep = null,
        public string $status = 'active',
        public ?string $fantasy_name = null,
        public ?string $state_registration = null,
        public ?string $municipal_registration = null,
        public ?string $founding_date = null,
        public ?string $industry = null,
        public ?string $company_size = null,
        public ?string $business_notes = null,
    ) {}

    /**
     * Cria uma instância de CustomerDTO a partir de um array de dados validados.
     */
    public static function fromRequest(array $data): self
    {
        $type = self::mapType($data['type'] ?? ($data['person_type'] ?? CommonData::TYPE_INDIVIDUAL));

        return new self(
            type: $type,
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            birth_date: DateHelper::parseBirthDate($data['birth_date'] ?? null),
            cpf: self::sanitizeNumbers($data['cpf'] ?? null),
            company_name: $data['company_name'] ?? null,
            cnpj: self::sanitizeNumbers($data['cnpj'] ?? null),
            description: $data['description'] ?? null,
            area_of_activity_id: isset($data['area_of_activity_id']) ? (int) $data['area_of_activity_id'] : null,
            profession_id: isset($data['profession_id']) ? (int) $data['profession_id'] : null,
            email: $data['email'] ?? $data['email_personal'] ?? null,
            phone: $data['phone'] ?? $data['phone_personal'] ?? null,
            email_personal: $data['email_personal'] ?? ($data['email'] ?? null),
            phone_personal: $data['phone_personal'] ?? ($data['phone'] ?? null),
            email_business: $data['email_business'] ?? null,
            phone_business: $data['phone_business'] ?? null,
            website: $data['website'] ?? null,
            address: $data['address'] ?? null,
            address_number: $data['address_number'] ?? null,
            neighborhood: $data['neighborhood'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            cep: self::sanitizeNumbers($data['cep'] ?? null),
            status: $data['status'] ?? 'active',
            fantasy_name: $data['fantasy_name'] ?? null,
            state_registration: $data['state_registration'] ?? null,
            municipal_registration: $data['municipal_registration'] ?? null,
            founding_date: DateHelper::parseBirthDate($data['founding_date'] ?? null),
            industry: $data['industry'] ?? null,
            company_size: $data['company_size'] ?? null,
            business_notes: $data['business_notes'] ?? ($data['notes'] ?? null),
        );
    }

    /**
     * Mapeia valores externos para tipos internos do CommonData.
     */
    private static function mapType(?string $external): string
    {
        $value = strtolower((string) $external);

        return match ($value) {
            'persona_fisica', 'pf', 'individual' => CommonData::TYPE_INDIVIDUAL,
            'persona_juridica', 'pj', 'company' => CommonData::TYPE_COMPANY,
            default => CommonData::TYPE_INDIVIDUAL,
        };
    }
}
