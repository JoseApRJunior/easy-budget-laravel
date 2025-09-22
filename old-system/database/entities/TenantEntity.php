<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class TenantEntity extends Entity
{
    public function __construct(
        public readonly string $name,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
