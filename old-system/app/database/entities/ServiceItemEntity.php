<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ServiceItemEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $service_id,
        public readonly int $product_id,
        public readonly ?float $unit_value = 0.00,
        public readonly ?int $quantity = 0,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
