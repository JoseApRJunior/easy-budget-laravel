<?php

namespace app\database\entitiesORM;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade que combina dados de Plan e PlanSubscription para relatórios e queries.
 *
 * Esta classe é uma view/join entity, não mapeada para tabela, usada para
 * acessar dados combinados sem consultas complexas.
 *
 * @package app\database\entitiesORM
 */
class PlansWithPlanSubscriptionEntity extends AbstractEntityORM
{
    /**
     * ID da assinatura.
     *
     * @var int|null
     */
    private ?int $id = null;

    /**
     * ID do tenant.
     *
     * @var int|null
     */
    private ?int $tenantId = null;

    /**
     * ID do provider.
     *
     * @var int|null
     */
    private ?int $providerId = null;

    /**
     * ID do plan.
     *
     * @var int|null
     */
    private ?int $planId = null;

    /**
     * Status da assinatura.
     *
     * @var string|null
     */
    private ?string $status = null;

    /**
     * Valor da transação.
     *
     * @var float|null
     */
    private ?float $transactionAmount = null;

    /**
     * Data de término.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $endDate = null;

    /**
     * Slug do plan.
     *
     * @var string|null
     */
    private ?string $slug = null;

    /**
     * Nome do plan.
     *
     * @var string|null
     */
    private ?string $name = null;

    /**
     * Data de criação.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Data de atualização.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * Construtor básico.
     */
    public function __construct()
    {
        // Inicialização vazia para DTO
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId( ?int $id ): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function setTenantId( ?int $tenantId ): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function getProviderId(): ?int
    {
        return $this->providerId;
    }

    public function setProviderId( ?int $providerId ): self
    {
        $this->providerId = $providerId;
        return $this;
    }

    public function getPlanId(): ?int
    {
        return $this->planId;
    }

    public function setPlanId( ?int $planId ): self
    {
        $this->planId = $planId;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus( ?string $status ): self
    {
        $this->status = $status;
        return $this;
    }

    public function getTransactionAmount(): ?float
    {
        return $this->transactionAmount;
    }

    public function setTransactionAmount( ?float $transactionAmount ): self
    {
        $this->transactionAmount = $transactionAmount;
        return $this;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate( ?DateTimeImmutable $endDate ): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug( ?string $slug ): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName( ?string $name ): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt( ?DateTimeImmutable $createdAt ): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt( ?DateTimeImmutable $updatedAt ): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

}