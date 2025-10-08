<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class UserRolesEntity extends Entity
{
    public function __construct(
        public readonly int $user_id,
        public readonly int $role_id,
        public readonly int $tenant_id,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
