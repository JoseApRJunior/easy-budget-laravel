<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\PlanEntity;
use app\database\entitiesORM\ProviderEntity;
use app\database\entitiesORM\TenantEntity;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade que representa as assinaturas de planos.
 *
 * @ORM\Entity
 * @ORM\Table(name="plan_subscriptions")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity ]
#[ORM\Table(name: 'plan_subscriptions') ]
#[ORM\HasLifecycleCallbacks ]
class PlanSubscriptionEntity extends AbstractEntityORM
{
    /**
     * @var int ID da assinatura.
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * @var string Status da assinatura.
     */
    #[ORM\Column(type: 'string') ]
    private string $status;

    /**
     * @var float Valor da transação.
     */
    #[ORM\Column(name: 'transaction_amount', type: 'float') ]
    private float $transactionAmount;

    /**
     * @var DateTimeImmutable Data de início da assinatura.
     */
    #[ORM\Column(name: 'start_date', type: 'datetime_immutable') ]
    private DateTimeImmutable $startDate;

    /**
     * @var DateTimeImmutable|null Data de término da assinatura.
     */
    #[ORM\Column(name: 'end_date', type: 'datetime_immutable', nullable: true) ]
    private ?DateTimeImmutable $endDate = null;

    /**
     * @var DateTimeImmutable|null Data da transação.
     */
    #[ORM\Column(name: 'transaction_date', type: 'datetime_immutable', nullable: true) ]
    private ?DateTimeImmutable $transactionDate = null;

    /**
     * @var string|null Método de pagamento.
     */
    #[ORM\Column(name: 'payment_method', type: 'string', length: 255, nullable: true) ]
    private ?string $paymentMethod = null;

    /**
     * @var string|null ID do pagamento na plataforma.
     */
    #[ORM\Column(name: 'payment_id', type: 'string', length: 255, nullable: true) ]
    private ?string $paymentId = null;

    /**
     * @var string|null Hash público para acesso externo.
     */
    #[ORM\Column(name: 'public_hash', type: 'string', length: 255, nullable: true, unique: true) ]
    private ?string $publicHash = null;

    /**
     * @var DateTimeImmutable|null Data do último pagamento.
     */
    #[ORM\Column(name: 'last_payment_date', type: 'datetime_immutable', nullable: true) ]
    private ?DateTimeImmutable $lastPaymentDate = null;

    /**
     * @var DateTimeImmutable|null Data do próximo pagamento.
     */
    #[ORM\Column(name: 'next_payment_date', type: 'datetime_immutable', nullable: true) ]
    private ?DateTimeImmutable $nextPaymentDate = null;

    /**
     * @var DateTimeImmutable Data de criação do registro.
     */
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable Data da última atualização do registro.
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $updatedAt;

    /**
     * @var TenantEntity Inquilino associado.
     */
    #[ORM\ManyToOne(targetEntity: TenantEntity::class) ]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false) ]
    private TenantEntity $tenant;

    /**
     * @var ProviderEntity Provedor de pagamento associado.
     */
    #[ORM\ManyToOne(targetEntity: ProviderEntity::class) ]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', nullable: false) ]
    private ProviderEntity $provider;

    /**
     * @var PlanEntity Plano associado.
     */
    #[ORM\ManyToOne(targetEntity: PlanEntity::class) ]
    #[ORM\JoinColumn(name: 'plan_id', referencedColumnName: 'id', nullable: false) ]
    private PlanEntity $plan;

    /**
     * @param TenantEntity $tenant
     * @param ProviderEntity $provider
     * @param PlanEntity $plan
     */
    public function __construct( TenantEntity $tenant, ProviderEntity $provider, PlanEntity $plan )
    {
        $this->tenant   = $tenant;
        $this->provider = $provider;
        $this->plan     = $plan;
    }

    /**
     * Atualiza a data de criação antes da persistência inicial.
     */
    #[ORM\PrePersist ]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Atualiza a data de modificação antes de cada atualização.
     */
    #[ORM\PreUpdate ]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus( string $status ): self
    {
        $this->status = $status;
        return $this;
    }

    public function getTransactionAmount(): float
    {
        return $this->transactionAmount;
    }

    public function setTransactionAmount( float $transactionAmount ): self
    {
        $this->transactionAmount = $transactionAmount;
        return $this;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate( DateTimeImmutable $startDate ): self
    {
        $this->startDate = $startDate;
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

    public function getTransactionDate(): ?DateTimeImmutable
    {
        return $this->transactionDate;
    }

    public function setTransactionDate( ?DateTimeImmutable $transactionDate ): self
    {
        $this->transactionDate = $transactionDate;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod( ?string $paymentMethod ): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function setPaymentId( ?string $paymentId ): self
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    public function getPublicHash(): ?string
    {
        return $this->publicHash;
    }

    public function setPublicHash( ?string $publicHash ): self
    {
        $this->publicHash = $publicHash;
        return $this;
    }

    public function getLastPaymentDate(): ?DateTimeImmutable
    {
        return $this->lastPaymentDate;
    }

    public function setLastPaymentDate( ?DateTimeImmutable $lastPaymentDate ): self
    {
        $this->lastPaymentDate = $lastPaymentDate;
        return $this;
    }

    public function getNextPaymentDate(): ?DateTimeImmutable
    {
        return $this->nextPaymentDate;
    }

    public function setNextPaymentDate( ?DateTimeImmutable $nextPaymentDate ): self
    {
        $this->nextPaymentDate = $nextPaymentDate;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt( DateTimeImmutable $updatedAt ): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getTenant(): TenantEntity
    {
        return $this->tenant;
    }

    public function setTenant( TenantEntity $tenant ): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    public function getProvider(): ProviderEntity
    {
        return $this->provider;
    }

    public function setProvider( ProviderEntity $provider ): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getPlan(): PlanEntity
    {
        return $this->plan;
    }

    public function setPlan( PlanEntity $plan ): self
    {
        $this->plan = $plan;
        return $this;
    }

}
