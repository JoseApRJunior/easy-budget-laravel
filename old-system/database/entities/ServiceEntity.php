<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ServiceEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly ?int $budget_id = null,
        public readonly ?int $category_id = null,
        public readonly ?int $service_statuses_id = null,
        public readonly ?string $code = null,
        public readonly ?string $description = null,
        public readonly ?string $pdf_verification_hash = null,
        public readonly ?float $discount = 0.00,
        public readonly ?float $total = 0.00,
        public readonly ?DateTime $due_date = new DateTime(),
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
