<?php

declare(strict_types=1);

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use app\database\repositories\ServiceStatusesRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[ORM\Entity(repositoryClass: ServiceStatusesRepository::class) ]
#[ORM\Table(name: 'service_statuses') ]
#[ORM\HasLifecycleCallbacks ]
class ServiceStatusesEntity extends AbstractEntityORM
{
    #[ORM\Id ]
    #[ORM\Column(type: Types::INTEGER) ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    /**
     * O ID do tenant ao qual este status pertence.
     *
     * Campo da tabela: `tenant_id`
     * Tipo no banco: INTEGER NOT NULL
     *
     * @var int
     */
    #[ORM\Column(type: Types::INTEGER, nullable: false) ]
    private int $tenantId;

    #[ORM\Column(type: Types::STRING) ]
    private string $slug;

    #[ORM\Column(type: Types::STRING) ]
    private string $name;

    #[ORM\Column(type: Types::STRING) ]
    private string $description;

    #[ORM\Column(type: Types::STRING) ]
    private string $color;

    #[ORM\Column(type: Types::STRING) ]
    private string $icon;

    #[ORM\Column(name: 'order_index', type: Types::INTEGER) ]
    private int $orderIndex;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN) ]
    private bool $isActive;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        int $tenantId,
        string $slug,
        string $name,
        string $description,
        string $color,
        string $icon,
        int $orderIndex,
        bool $isActive,
    ) {
        $this->tenantId    = $tenantId;
        $this->slug        = $slug;
        $this->name        = $name;
        $this->description = $description;
        $this->color       = $color;
        $this->icon        = $icon;
        $this->orderIndex  = $orderIndex;
        $this->isActive    = $isActive;
        $this->createdAt   = new DateTimeImmutable( 'now' );
        $this->updatedAt   = new DateTimeImmutable( 'now' );
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

    /**
     * Obtém o ID do tenant.
     *
     * @return int
     */
    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    /**
     * Define o ID do tenant.
     *
     * @param int $tenantId
     * @return self
     */
    public function setTenantId( int $tenantId ): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug( string $slug ): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName( string $name ): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription( string $description ): self
    {
        $this->description = $description;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor( string $color ): self
    {
        $this->color = $color;
        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon( string $icon ): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex( int $orderIndex ): self
    {
        $this->orderIndex = $orderIndex;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive( bool $isActive ): self
    {
        $this->isActive = $isActive;
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

}