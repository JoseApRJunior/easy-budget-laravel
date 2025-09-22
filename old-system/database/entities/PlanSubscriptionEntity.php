<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class PlanSubscriptionEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $provider_id,
        public readonly int $plan_id,
        public readonly string $status,
        public readonly float $transaction_amount,
        public readonly DateTime $start_date,
        public readonly ?DateTime $end_date = null,
        public readonly ?DateTime $transaction_date = null,
        public readonly ?string $payment_method = null,
        public readonly ?string $payment_id = null,
        public readonly ?string $public_hash = null,
        public readonly ?DateTime $last_payment_date = null,
        public readonly ?DateTime $next_payment_date = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime(),
        public readonly ?int $id = null,
    ) {}

}