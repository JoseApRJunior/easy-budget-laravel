<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class CustomerEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $common_data_id,
        public readonly int $contact_id,
        public readonly int $address_id,
        public readonly ?string $status = null,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
