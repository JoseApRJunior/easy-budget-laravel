<?php

namespace app\database\entitiesORM;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="reports")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity ]
#[ORM\Table(name: 'reports') ]
#[ORM\HasLifecycleCallbacks ]
class ReportEntity extends AbstractEntityORM
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue ]
    #[ORM\Column(type: 'integer') ]
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
     * @var UserEntity
     * @ORM\ManyToOne(targetEntity=UserEntity::class)
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    #[ORM\ManyToOne(targetEntity: UserEntity::class) ]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false) ]
    private UserEntity $user;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: 'string') ]
    private string $hash;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: 'string') ]
    private string $type;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: 'string') ]
    private string $description;

    /**
     * @var string
     * @ORM\Column(name="file_name", type="string")
     */
    #[ORM\Column(name: 'file_name', type: 'string') ]
    private string $fileName;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: 'string') ]
    private string $status;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: 'string') ]
    private string $format;

    /**
     * @var float
     * @ORM\Column(type="float")
     */
    #[ORM\Column(type: 'float') ]
    private float $size;

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
     * @param UserEntity $user
     * @param string $hash
     * @param string $type
     * @param string $description
     * @param string $fileName
     * @param string $status
     * @param string $format
     * @param float $size
     */
    public function __construct(
        TenantEntity $tenant,
        UserEntity $user,
        string $hash,
        string $type,
        string $description,
        string $fileName,
        string $status,
        string $format,
        float $size,
    ) {
        $this->tenant      = $tenant;
        $this->user        = $user;
        $this->hash        = $hash;
        $this->type        = $type;
        $this->description = $description;
        $this->fileName    = $fileName;
        $this->status      = $status;
        $this->format      = $format;
        $this->size        = $size;
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
     * @return UserEntity
     */
    public function getUser(): UserEntity
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType( string $type ): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription( string $description ): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     * @return $this
     */
    public function setFileName( string $fileName ): self
    {
        $this->fileName = $fileName;
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
     * @return $this
     */
    public function setStatus( string $status ): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string $format
     * @return $this
     */
    public function setFormat( string $format ): self
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @return float
     */
    public function getSize(): float
    {
        return $this->size;
    }

    /**
     * @param float $size
     * @return $this
     */
    public function setSize( float $size ): self
    {
        $this->size = $size;
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