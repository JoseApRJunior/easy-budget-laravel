<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class UserEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $email,
        public readonly bool $is_active = false,
        public readonly ?string $password = null,
        public readonly ?string $logo = null,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
