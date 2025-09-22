<?php

declare(strict_types=1);

namespace app\database\entitiesORM;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[ORM\Entity ]
#[ORM\Table(name: 'user_confirmation_tokens') ]
#[ORM\HasLifecycleCallbacks ]
class UserConfirmationTokenEntity extends AbstractEntityORM
{
    #[ORM\Id ]
    #[ORM\Column(type: Types::INTEGER) ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: Types::INTEGER) ]
    private int $userId;

    #[ORM\Column(name: 'tenant_id', type: Types::INTEGER) ]
    private int $tenantId;

    #[ORM\Column(type: Types::STRING, nullable: true) ]
    private ?string $token;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE, nullable: true) ]
    private ?DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $updatedAt;

    public function __construct( int $userId, int $tenantId, ?string $token, ?DateTimeImmutable $expiresAt )
    {
        $this->userId    = $userId;
        $this->tenantId  = $tenantId;
        $this->token     = $token;
        $this->expiresAt = $expiresAt;
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

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId( int $userId ): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function setTenantId( int $tenantId ): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken( ?string $token ): self
    {
        $this->token = $token;
        return $this;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt( ?DateTimeImmutable $expiresAt ): self
    {
        $this->expiresAt = $expiresAt;
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