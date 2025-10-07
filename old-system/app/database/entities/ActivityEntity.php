<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ActivityEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $user_id,
        public readonly string $action_type,
        public readonly string $entity_type,
        public readonly int $entity_id,
        public readonly string $description,
        public readonly ?array $metadata = [],
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime()
    ) {
    }

}
