<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class BudgetStatusesEntity extends Entity
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $description,
        public readonly string $color,
        public readonly string $icon,
        public readonly int $order_index,
        public readonly int $is_active,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
