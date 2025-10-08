<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class InvoiceEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $service_id,
        public readonly int $customer_id,
        public readonly string $code,
        public readonly int $invoice_statuses_id,
        public readonly float $subtotal,
        public readonly float $total,
        public readonly DateTime $due_date,
        public readonly ?DateTime $transaction_date = null,
        public readonly ?string $payment_method = null,
        public readonly ?string $payment_id = null,
        public readonly ?float $transaction_amount = null,
        public readonly ?string $public_hash = null,
        public readonly ?float $discount = null,
        public readonly ?string $notes = null,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime(),
    ) {}

}
