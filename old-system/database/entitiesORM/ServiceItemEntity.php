<?php

namespace app\database\entitiesORM;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity ]
#[ORM\Table(name: 'service_items') ]
#[ORM\HasLifecycleCallbacks ]
class ServiceItemEntity extends AbstractEntityORM
{
    #[ORM\Id ]
    #[ORM\Column(type: Types::INTEGER) ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ServiceEntity::class, inversedBy: 'serviceItems') ]
    #[ORM\JoinColumn(nullable: false) ]
    private ServiceEntity $service;

    #[ORM\ManyToOne(targetEntity: ProductEntity::class) ]
    #[ORM\JoinColumn(nullable: false) ]
    private ProductEntity $product;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2) ]
    private float $unitValue;

    #[ORM\Column(type: Types::INTEGER) ]
    private int $quantity;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $updatedAt;

    /**
     * ID do tenant (inquilino) ao qual o item de serviço pertence.
     *
     * @var int|null
     */
    #[ORM\Column(name: 'tenant_id', type: Types::INTEGER, nullable: true) ]
    private ?int $tenantId = null;

    public function __construct(
        ServiceEntity $service,
        ProductEntity $product,
        float $unitValue,
        int $quantity,
    ) {
        $this->service   = $service;
        $this->product   = $product;
        $this->unitValue = $unitValue;
        $this->quantity  = $quantity;
        $this->createdAt = new DateTimeImmutable( 'now' );
        $this->updatedAt = new DateTimeImmutable( 'now' );
    }

    #[ORM\PreUpdate ]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable( 'now' );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getService(): ServiceEntity
    {
        return $this->service;
    }

    public function setService( ServiceEntity $service ): self
    {
        $this->service = $service;
        return $this;
    }

    public function getProduct(): ProductEntity
    {
        return $this->product;
    }

    public function setProduct( ProductEntity $product ): self
    {
        $this->product = $product;
        return $this;
    }

    public function getUnitValue(): float
    {
        return $this->unitValue;
    }

    public function setUnitValue( float $unitValue ): self
    {
        $this->unitValue = $unitValue;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity( int $quantity ): self
    {
        $this->quantity = $quantity;
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

    /**
     * Obtém o ID do tenant.
     *
     * @return int|null
     */
    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    /**
     * Define o ID do tenant.
     *
     * @param int|null $tenantId
     * @return self
     */
    public function setTenantId( ?int $tenantId ): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Calcula o preço total do item (valor unitário * quantidade).
     *
     * @return float
     */
    public function getTotalPrice(): float
    {
        return $this->unitValue * $this->quantity;
    }

}