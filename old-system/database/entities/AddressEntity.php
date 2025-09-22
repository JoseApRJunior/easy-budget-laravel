<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class AddressEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly ?string $address = null,
        public readonly ?string $address_number = null,
        public readonly ?string $neighborhood = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $cep = null,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime(),
    ) {}

}
