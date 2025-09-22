<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ActivityEntity extends Entity
{
    /**
     * Construtor da entidade de atividade.
     *
     * @param int $tenant_id ID do tenant
     * @param int $user_id ID do usuário
     * @param string $action_type Tipo de ação
     * @param string $entity_type Tipo da entidade
     * @param int $entity_id ID da entidade
     * @param string $description Descrição da atividade
     * @param array<string, mixed>|null $metadata Metadados da atividade
     * @param int|null $id ID da atividade
     * @param DateTime|null $created_at Data de criação
     */
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
