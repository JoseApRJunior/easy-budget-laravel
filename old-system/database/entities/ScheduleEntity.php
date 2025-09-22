<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ScheduleEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $service_id,
        public readonly int $user_confirmation_token_id,
        public readonly ?DateTime $start_date_time = null,
        public readonly ?string $location = null,
        public readonly ?DateTime $end_date_time = null,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
