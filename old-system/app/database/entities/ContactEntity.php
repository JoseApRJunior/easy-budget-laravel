<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ContactEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $email,
        public readonly string $phone,
        public readonly ?string $email_business = null,
        public readonly ?string $phone_business = null,
        public readonly ?string $website = null,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
