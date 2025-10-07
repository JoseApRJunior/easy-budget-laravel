<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class StatusEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $color,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime,
        public readonly ?DateTime $updated_at = new DateTime
    ) {}

}