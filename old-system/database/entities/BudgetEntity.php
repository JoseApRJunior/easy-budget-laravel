<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class BudgetEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly ?int $customer_id = null,
        public readonly ?string $code = null,
        public readonly ?int $budget_statuses_id = null,
        public readonly ?int $user_confirmation_token_id = null,
        public readonly ?DateTime $due_date = null,
        public readonly ?float $discount = 0.00,
        public readonly ?float $total = 0.00,
        public readonly ?string $description = null,
        public readonly ?string $payment_terms = null,
        public readonly array $attachment = null,
        public readonly array $history = null,
        public readonly ?string $pdf_verification_hash = null,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime(),
    ) {}

}