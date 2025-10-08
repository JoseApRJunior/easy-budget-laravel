<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class PlanEntity extends Entity
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly float $price,
        public readonly bool $status,
        public readonly int $max_budgets,
        public readonly int $max_clients,
        public readonly array $features = [],
        public readonly ?int $id = null,
        public readonly ?string $description = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
