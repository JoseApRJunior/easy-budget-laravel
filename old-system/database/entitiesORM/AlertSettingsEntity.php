<?php

declare(strict_types=1);

namespace app\database\entitiesORM;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade para configurações de alertas
 */
#[ORM\Entity ]
#[ORM\Table(name: 'alert_settings') ]
class AlertSettingsEntity extends AbstractEntityORM
{
    #[ORM\Id ]
    #[ORM\GeneratedValue ]
    #[ORM\Column(name: 'id', type: 'integer') ]
    private ?int $id = null;

    #[ORM\Column(name: 'settings', type: 'json') ]
    private array $settings;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable') ]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true) ]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings( array $settings ): self
    {
        $this->settings  = $settings;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function jsonSerialize(): array
    {
        return [ 
            'id'         => $this->id,
            'settings'   => $this->settings,
            'created_at' => $this->createdAt->format( 'Y-m-d H:i:s' ),
            'updated_at' => $this->updatedAt?->format( 'Y-m-d H:i:s' )
        ];
    }

}
