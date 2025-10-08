<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ProviderFullEntity extends Entity
{
    public function __construct(
        public readonly int $id,
        public readonly int $tenant_id,
        public readonly int $user_id,
        public readonly int $common_data_id,
        public readonly int $contact_id,
        public readonly int $address_id,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $phone_business,
        public readonly int $is_active,
        public readonly int $terms_accepted,
        public readonly ?string $password,
        public readonly ?string $email_business = null,
        public readonly ?string $logo = null,
        public readonly ?DateTime $birth_date = null,
        public readonly ?string $cnpj = null,
        public readonly ?string $cpf = null,
        public readonly ?string $company_name = null,
        public readonly ?int $area_of_activity_id = null,
        public readonly ?int $profession_id = null,
        public readonly ?string $description = null,
        public readonly ?string $website = null,
        public readonly ?string $address = null,
        public readonly ?string $address_number = null,
        public readonly ?string $neighborhood = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $cep = null,
        public readonly ?DateTime $created_at = new DateTime,
        public readonly ?DateTime $updated_at = new DateTime
    ) {}

}