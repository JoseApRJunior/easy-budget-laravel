<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\TenantEntity;
use app\database\repositories\UserRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[ORM\Entity(repositoryClass: UserRepository::class) ]
#[ORM\Table(name: 'users') ]
#[ORM\HasLifecycleCallbacks ]
class UserEntity extends AbstractEntityORM
{
    #[ORM\Id ]
    #[ORM\Column(type: Types::INTEGER) ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TenantEntity::class) ]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id') ]
    private TenantEntity $tenant;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true) ]
    private string $email;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true) ]
    private ?string $password = null;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN) ]
    private bool $isActive = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true) ]
    private ?string $logo = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $updatedAt;

    public function __construct( TenantEntity $tenant, string $email )
    {
        $this->tenant    = $tenant;
        $this->email     = $email;
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

    public function getTenant(): TenantEntity
    {
        return $this->tenant;
    }

    public function setTenant( TenantEntity $tenant ): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail( string $email ): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword( ?string $password ): self
    {
        $this->password = $password;
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

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo( ?string $logo ): self
    {
        $this->logo = $logo;
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
