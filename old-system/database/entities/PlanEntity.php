<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class PlanEntity extends Entity
{
    /**
     * Construtor da entidade de plano.
     *
     * @param string $name Nome do plano
     * @param string $slug Slug do plano
     * @param float $price Preço do plano
     * @param bool $status Status do plano
     * @param int $max_budgets Máximo de orçamentos
     * @param int $max_clients Máximo de clientes
     * @param array<string, mixed> $features Funcionalidades do plano
     * @param int|null $id ID do plano
     * @param string|null $description Descrição do plano
     * @param DateTime|null $created_at Data de criação
     * @param DateTime|null $updated_at Data de atualização
     */
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly float $price,
        public readonly bool $status,
        public readonly int $max_budgets,
        public readonly int $max_clients,
        public readonly array $features = [],
        public readonly ?int $id = null,
        public readonly ?string $description = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime()
    ) {
    }

}
