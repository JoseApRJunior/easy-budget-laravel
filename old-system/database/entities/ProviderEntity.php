<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ProviderEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $user_id,
        public readonly int $common_data_id,
        public readonly ?int $contact_id = null,
        public readonly ?int $address_id = null,
        public readonly bool $terms_accepted = false,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime(),
    ) {}

}
