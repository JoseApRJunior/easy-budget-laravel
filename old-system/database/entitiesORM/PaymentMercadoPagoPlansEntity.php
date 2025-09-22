<?php

namespace app\database\entitiesORM;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade que representa os pagamentos de planos via Mercado Pago.
 *
 * @ORM\Entity
 * @ORM\Table(name="payment_mercado_pago_plans")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity ]
#[ORM\Table(name: 'payment_mercado_pago_plans') ]
#[ORM\HasLifecycleCallbacks ]
class PaymentMercadoPagoPlansEntity extends AbstractEntityORM
{
    /**
     * @var int ID do registro.
     */
    #[ORM\Id ]
    #[ORM\Column(type: 'integer') ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    /**
     * @var string ID do pagamento no Mercado Pago.
     */
    #[ORM\Column(name: 'payment_id', type: 'string') ]
    private string $paymentId;

    /**
     * @var TenantEntity Inquilino associado ao pagamento.
     */
    #[ORM\ManyToOne(targetEntity: TenantEntity::class) ]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false) ]
    private TenantEntity $tenant;

    /**
     * @var ProviderEntity Provedor associado ao pagamento.
     */
    #[ORM\ManyToOne(targetEntity: ProviderEntity::class) ]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', nullable: false) ]
    private ProviderEntity $provider;

    /**
     * @var PlanSubscriptionEntity Assinatura de plano associada ao pagamento.
     */
    #[ORM\ManyToOne(targetEntity: PlanSubscriptionEntity::class) ]
    #[ORM\JoinColumn(name: 'plan_subscription_id', referencedColumnName: 'id', nullable: false) ]
    private PlanSubscriptionEntity $planSubscription;

    /**
     * @var string Status do pagamento.
     */
    #[ORM\Column(type: 'string') ]
    private string $status;

    /**
     * @var string Método de pagamento utilizado.
     */
    #[ORM\Column(name: 'payment_method', type: 'string') ]
    private string $paymentMethod;

    /**
     * @var float Valor da transação.
     */
    #[ORM\Column(name: 'transaction_amount', type: 'float') ]
    private float $transactionAmount;

    /**
     * @var DateTimeImmutable|null Data da transação.
     */
    #[ORM\Column(name: 'transaction_date', type: 'datetime_immutable', nullable: true) ]
    private ?DateTimeImmutable $transactionDate;

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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    /**
     * @param string $paymentId
     * @return self
     */
    public function setPaymentId( string $paymentId ): self
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    /**
     * @return TenantEntity
     */
    public function getTenant(): TenantEntity
    {
        return $this->tenant;
    }

    /**
     * @param TenantEntity $tenant
     * @return self
     */
    public function setTenant( TenantEntity $tenant ): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    /**
     * @return ProviderEntity
     */
    public function getProvider(): ProviderEntity
    {
        return $this->provider;
    }

    /**
     * @param ProviderEntity $provider
     * @return self
     */
    public function setProvider( ProviderEntity $provider ): self
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * @return PlanSubscriptionEntity
     */
    public function getPlanSubscription(): PlanSubscriptionEntity
    {
        return $this->planSubscription;
    }

    /**
     * @param PlanSubscriptionEntity $planSubscription
     * @return self
     */
    public function setPlanSubscription( PlanSubscriptionEntity $planSubscription ): self
    {
        $this->planSubscription = $planSubscription;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return self
     */
    public function setStatus( string $status ): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     * @return self
     */
    public function setPaymentMethod( string $paymentMethod ): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * @return float
     */
    public function getTransactionAmount(): float
    {
        return $this->transactionAmount;
    }

    /**
     * @param float $transactionAmount
     * @return self
     */
    public function setTransactionAmount( float $transactionAmount ): self
    {
        $this->transactionAmount = $transactionAmount;
        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getTransactionDate(): ?DateTimeImmutable
    {
        return $this->transactionDate;
    }

    /**
     * @param DateTimeImmutable|null $transactionDate
     * @return self
     */
    public function setTransactionDate( ?DateTimeImmutable $transactionDate ): self
    {
        $this->transactionDate = $transactionDate;
        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

}