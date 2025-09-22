<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ProfessionEntity extends Entity
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly ?int $tenantId = null,
        public readonly bool $is_active = true,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime(),
    ) {}

}