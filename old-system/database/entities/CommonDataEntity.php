<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;
use DateTimeImmutable;

class CommonDataEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly ?DateTimeImmutable $birth_date = null,
        public readonly ?string $cnpj = null,
        public readonly ?string $cpf = null,
        public readonly ?string $company_name = null,
        public readonly ?string $description = null,
        public readonly ?int $area_of_activity_id = null,
        public readonly ?int $profession_id = null,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime(),
    ) {}

}