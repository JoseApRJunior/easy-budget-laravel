<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ProductEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?float $price = 0.00,
        public readonly bool $active = false,
        public readonly ?string $code = null,
        public readonly ?string $image = null,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
