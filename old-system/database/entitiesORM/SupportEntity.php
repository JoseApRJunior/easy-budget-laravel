<?php

namespace app\database\entitiesORM;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[ORM\Entity ]
#[ORM\Table(name: 'support') ]
#[ORM\HasLifecycleCallbacks ]
class SupportEntity extends AbstractEntityORM
{
    #[ORM\Id ]
    #[ORM\Column(type: Types::INTEGER) ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    #[ORM\Column(name: 'first_name', type: Types::STRING) ]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: Types::STRING) ]
    private string $lastName;

    #[ORM\Column(type: Types::STRING) ]
    private string $email;

    #[ORM\Column(type: Types::STRING) ]
    private string $subject;

    #[ORM\Column(type: Types::TEXT) ]
    private string $message;

    #[ORM\Column(name: 'tenant_id', type: Types::INTEGER, nullable: true) ]
    private ?int $tenantId;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        string $subject,
        string $message,
        ?int $tenantId = null,
    ) {
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->email     = $email;
        $this->subject   = $subject;
        $this->message   = $message;
        $this->tenantId  = $tenantId;
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

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName( string $firstName ): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName( string $lastName ): self
    {
        $this->lastName = $lastName;
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

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject( string $subject ): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage( string $message ): self
    {
        $this->message = $message;
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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

}