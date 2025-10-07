<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class MerchantOrderMercadoPagoEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $provider_id,
        public readonly string $merchant_order_id,
        public readonly int $plan_subscription_id,
        public readonly string $status,
        public readonly string $order_status,
        public readonly float $total_amount,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime(),
        public readonly ?int $id = null,
    ) {
    }

}
