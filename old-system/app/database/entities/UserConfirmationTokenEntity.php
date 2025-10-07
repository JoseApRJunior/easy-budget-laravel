<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class UserConfirmationTokenEntity extends Entity
{
    public function __construct(
        public readonly int $user_id,
        public readonly int $tenant_id,
        public readonly ?string $token = null,
        public readonly ?int $id = null,
        public readonly ?DateTime $expires_at = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
