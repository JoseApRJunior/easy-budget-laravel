<?php

namespace app\database\entitiesORM;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="merchant_orders_mercado_pago")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity ]
#[ORM\Table(name: 'merchant_orders_mercado_pago') ]
#[ORM\HasLifecycleCallbacks ]
class MerchantOrderMercadoPagoEntity extends AbstractEntityORM
{
    /**
     * @var int|null
     */
    #[ORM\Id ]
    #[ORM\Column(type: 'integer') ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    /**
     * @var TenantEntity
     */
    #[ORM\ManyToOne(targetEntity: TenantEntity::class) ]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id') ]
    private TenantEntity $tenant;

    /**
     * @var ProviderEntity
     */
    #[ORM\ManyToOne(targetEntity: ProviderEntity::class) ]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id') ]
    private ProviderEntity $provider;

    /**
     * @var string
     */
    #[ORM\Column(name: 'merchant_order_id', type: 'string') ]
    private string $merchantOrderId;

    /**
     * @var PlanSubscriptionEntity
     */
    #[ORM\ManyToOne(targetEntity: PlanSubscriptionEntity::class) ]
    #[ORM\JoinColumn(name: 'plan_subscription_id', referencedColumnName: 'id') ]
    private PlanSubscriptionEntity $planSubscription;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $status;

    /**
     * @var string
     */
    #[ORM\Column(name: 'order_status', type: 'string') ]
    private string $orderStatus;

    /**
     * @var float
     */
    #[ORM\Column(name: 'total_amount', type: 'float') ]
    private float $totalAmount;

    /**
     * @var DateTimeImmutable
     */
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $updatedAt;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return string
     */
    public function getMerchantOrderId(): string
    {
        return $this->merchantOrderId;
    }

    /**
     * @param string $merchantOrderId
     * @return self
     */
    public function setMerchantOrderId( string $merchantOrderId ): self
    {
        $this->merchantOrderId = $merchantOrderId;
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
    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }

    /**
     * @param string $orderStatus
     * @return self
     */
    public function setOrderStatus( string $orderStatus ): self
    {
        $this->orderStatus = $orderStatus;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    /**
     * @param float $totalAmount
     * @return self
     */
    public function setTotalAmount( float $totalAmount ): self
    {
        $this->totalAmount = $totalAmount;
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

    /**
     * @ORM\PrePersist
     */
    #[ORM\PrePersist ]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable( 'now' );
        $this->updatedAt = new DateTimeImmutable( 'now' );
    }

    /**
     * @ORM\PreUpdate
     */
    #[ORM\PreUpdate ]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable( 'now' );
    }

}
