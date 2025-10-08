<?php

namespace app\database\entities;

use core\dbal\Entity;

class InvoiceStatusesEntity extends Entity
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly string $color,
        public readonly string $icon,
        public readonly ?string $description = null,
        public readonly ?int $id = null,
    ) {
    }

}
