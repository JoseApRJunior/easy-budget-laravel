<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class PlansWithPlanSubscriptionEntity extends Entity
{
    public function __construct(
        // Campos da tabela plains
        public readonly ?string $slug = null,
        public readonly ?string $name = null,
        // Campos da tabela plan_subscriptions
        public readonly ?int $id = null,
        public readonly ?int $tenant_id = null,
        public readonly ?int $provider_id = null,
        public readonly ?int $plan_id = null,
        public readonly ?string $status = null,
        public readonly ?float $transaction_amount = null,
        public readonly ?DateTime $end_date = null,
    ) {
    }

}
