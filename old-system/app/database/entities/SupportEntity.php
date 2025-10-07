<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class SupportEntity extends Entity
{
    public function __construct(
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly string $email,
        public readonly string $subject,
        public readonly string $message,
        public readonly ?int $id = null,
        public readonly ?int $tenant_id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
