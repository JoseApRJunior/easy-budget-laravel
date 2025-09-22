<?php

namespace app\database\entitiesORM;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @ORM\Entity
 * @ORM\Table(name="schedules")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity ]
#[ORM\Table(name: 'schedules') ]
#[ORM\HasLifecycleCallbacks ]
class ScheduleEntity extends AbstractEntityORM
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id ]
    #[ORM\Column(type: 'integer') ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    /**
     * @var TenantEntity
     * @ORM\ManyToOne(targetEntity=TenantEntity::class)
     * @ORM\JoinColumn(name="tenant_id", referencedColumnName="id", nullable=false)
     */
    #[ORM\ManyToOne(targetEntity: TenantEntity::class) ]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false) ]
    private TenantEntity $tenant;

    /**
     * @var ServiceEntity
     * @ORM\ManyToOne(targetEntity=ServiceEntity::class)
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id", nullable=false)
     */
    #[ORM\ManyToOne(targetEntity: ServiceEntity::class) ]
    #[ORM\JoinColumn(name: 'service_id', referencedColumnName: 'id', nullable: false) ]
    private ServiceEntity $service;

    /**
     * @var UserConfirmationTokenEntity
     * @ORM\OneToOne(targetEntity=UserConfirmationTokenEntity::class)
     * @ORM\JoinColumn(name="user_confirmation_token_id", referencedColumnName="id", nullable=false)
     */
    #[ORM\OneToOne(targetEntity: UserConfirmationTokenEntity::class) ]
    #[ORM\JoinColumn(name: 'user_confirmation_token_id', referencedColumnName: 'id', nullable: false) ]
    private UserConfirmationTokenEntity $userConfirmationToken;

    /**
     * @var DateTimeImmutable|null
     * @ORM\Column(name="start_date_time", type="datetime_immutable", nullable=true)
     */
    #[ORM\Column(name: 'start_date_time', type: 'datetime_immutable', nullable: true) ]
    private ?DateTimeImmutable $startDateTime;

    /**
     * @var string|null
     * @ORM\Column(name="location", type="string", nullable=true)
     */
    #[ORM\Column(name: 'location', type: 'string', nullable: true) ]
    private ?string $location;

    /**
     * @var DateTimeImmutable|null
     * @ORM\Column(name="end_date_time", type="datetime_immutable", nullable=true)
     */
    #[ORM\Column(name: 'end_date_time', type: 'datetime_immutable', nullable: true) ]
    private ?DateTimeImmutable $endDateTime;

    /**
     * @var DateTimeImmutable
     * @ORM\Column(name="created_at", type="datetime_immutable")
     */
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable
     * @ORM\Column(name="updated_at", type="datetime_immutable")
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $updatedAt;

    /**
     * @param TenantEntity $tenant
     * @param ServiceEntity $service
     * @param UserConfirmationTokenEntity $userConfirmationToken
     */
    public function __construct( TenantEntity $tenant, ServiceEntity $service, UserConfirmationTokenEntity $userConfirmationToken )
    {
        $this->tenant                = $tenant;
        $this->service               = $service;
        $this->userConfirmationToken = $userConfirmationToken;
    }

    /**
     * @ORM\PrePersist
     */
    #[ORM\PrePersist ]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @ORM\PreUpdate
     */
    #[ORM\PreUpdate ]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

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
     * @return ServiceEntity
     */
    public function getService(): ServiceEntity
    {
        return $this->service;
    }

    /**
     * @param ServiceEntity $service
     * @return self
     */
    public function setService( ServiceEntity $service ): self
    {
        $this->service = $service;
        return $this;
    }

    /**
     * @return UserConfirmationTokenEntity
     */
    public function getUserConfirmationToken(): UserConfirmationTokenEntity
    {
        return $this->userConfirmationToken;
    }

    /**
     * @param UserConfirmationTokenEntity $userConfirmationToken
     * @return self
     */
    public function setUserConfirmationToken( UserConfirmationTokenEntity $userConfirmationToken ): self
    {
        $this->userConfirmationToken = $userConfirmationToken;
        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getStartDateTime(): ?DateTimeImmutable
    {
        return $this->startDateTime;
    }

    /**
     * @param DateTimeImmutable|null $startDateTime
     * @return self
     */
    public function setStartDateTime( ?DateTimeImmutable $startDateTime ): self
    {
        $this->startDateTime = $startDateTime;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * @param string|null $location
     * @return self
     */
    public function setLocation( ?string $location ): self
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getEndDateTime(): ?DateTimeImmutable
    {
        return $this->endDateTime;
    }

    /**
     * @param DateTimeImmutable|null $endDateTime
     * @return self
     */
    public function setEndDateTime( ?DateTimeImmutable $endDateTime ): self
    {
        $this->endDateTime = $endDateTime;
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
