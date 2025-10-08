<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class PaymentMercadoPagoInvoicesEntity extends Entity
{
    public function __construct(
        public readonly string $payment_id,
        public readonly int $tenant_id,
        public readonly int $invoice_id,
        public readonly string $status,
        public readonly string $payment_method,
        public readonly float $transaction_amount,
        public readonly ?DateTime $transaction_date = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime(),
        public readonly ?int $id = null,
    ) {
    }

}
