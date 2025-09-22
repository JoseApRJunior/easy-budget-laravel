<?php

namespace app\database\entitiesORM;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="resources")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity ]
#[ORM\Table(name: 'resources') ]
#[ORM\HasLifecycleCallbacks ]
class ResourceEntity extends AbstractEntityORM
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
     * @var string
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: 'string') ]
    private string $name;

    /**
     * @var string
     * @ORM\Column(type="string", unique=true)
     */
    #[ORM\Column(type: 'string', unique: true) ]
    private string $slug;

    /**
     * @var bool
     * @ORM\Column(name="in_dev", type="boolean")
     */
    #[ORM\Column(name: 'in_dev', type: 'boolean') ]
    private bool $inDev;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: 'string') ]
    private string $status;

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
     * @param string $name
     * @param string $slug
     * @param bool $inDev
     * @param string $status
     */
    public function __construct( string $name, string $slug, bool $inDev, string $status )
    {
        $this->name   = $name;
        $this->slug   = $slug;
        $this->inDev  = $inDev;
        $this->status = $status;
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName( string $name ): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     * @return $this
     */
    public function setSlug( string $slug ): self
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return bool
     */
    public function isInDev(): bool
    {
        return $this->inDev;
    }

    /**
     * @param bool $inDev
     * @return $this
     */
    public function setInDev( bool $inDev ): self
    {
        $this->inDev = $inDev;
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