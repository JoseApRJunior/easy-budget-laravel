<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ResourceEntity extends Entity
{
    // Constantes para status possíveis
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_DELETED = 'deleted';

    // Array de status válidos
    public const VALID_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_DELETED,
    ];

    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly bool $in_dev,
        public readonly string $status = self::STATUS_ACTIVE,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
        // Validar status
        if (!in_array($status, self::VALID_STATUSES)) {
            throw new \InvalidArgumentException('Status inválido');
        }
    }

    // Método helper para verificar se recurso está ativo
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

}
