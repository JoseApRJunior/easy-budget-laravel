<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity ]
#[ORM\Table(name: 'units') ]
#[ORM\HasLifecycleCallbacks ]
class UnitEntity extends AbstractEntityORM
{
    #[ORM\Id ]
    #[ORM\Column(type: 'integer') ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    #[ORM\Column(type: 'string', unique: true) ]
    private string $slug;

    #[ORM\Column(type: 'string') ]
    private string $name;

    #[ORM\Column(type: 'boolean') ]
    private bool $is_active;

    #[ORM\Column(name: 'created_at', type: 'datetime') ]
    private DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime') ]
    private DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug( string $slug ): void
    {
        $this->slug = $slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName( string $name ): void
    {
        $this->name = $name;
    }

    public function getIsActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive( bool $is_active ): void
    {
        $this->is_active = $is_active;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt( DateTime $createdAt ): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt( DateTime $updatedAt ): void
    {
        $this->updatedAt = $updatedAt;
    }

}
