<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use app\database\repositories\TenantRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[ORM\Entity(repositoryClass: TenantRepository::class) ]
#[ORM\Table(name: 'tenants') ]
#[ORM\HasLifecycleCallbacks ]
class TenantEntity extends AbstractEntityORM
{
    #[ORM\Id ]
    #[ORM\Column(type: Types::INTEGER) ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, unique: true) ]
    private string $name;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $updatedAt;

    public function __construct( string $name )
    {
        $this->name      = $name;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName( string $name ): self
    {
        $this->name = $name;
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