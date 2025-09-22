<?php

namespace app\database\entitiesORM;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade que representa os planos do sistema.
 *
 * @ORM\Entity
 * @ORM\Table(name="plans")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity ]
#[ORM\Table(name: 'plans') ]
#[ORM\HasLifecycleCallbacks ]
class PlanEntity extends AbstractEntityORM
{
    /**
     * @var int ID do plano.
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * @var string Nome do plano.
     */
    #[ORM\Column(type: 'string', length: 255) ]
    private string $name;

    /**
     * @var string Slug único para o plano.
     */
    #[ORM\Column(type: 'string', length: 255, unique: true) ]
    private string $slug;

    /**
     * @var float Preço do plano.
     */
    #[ORM\Column(type: 'float') ]
    private float $price;

    /**
     * @var bool Status do plano (ativo/inativo).
     */
    #[ORM\Column(type: 'boolean') ]
    private bool $status;

    /**
     * @var int Número máximo de orçamentos permitidos.
     */
    #[ORM\Column(name: 'max_budgets', type: 'integer') ]
    private int $maxBudgets;

    /**
     * @var int Número máximo de clientes permitidos.
     */
    #[ORM\Column(name: 'max_clients', type: 'integer') ]
    private int $maxClients;

    /**
     * @var array<string> Recursos do plano.
     */
    #[ORM\Column(type: 'json') ]
    private array $features = [];

    /**
     * @var string|null Descrição do plano.
     */
    #[ORM\Column(type: 'text', nullable: true) ]
    private ?string $description = null;

    /**
     * @var DateTimeImmutable Data de criação do registro.
     */
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable Data da última atualização do registro.
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $updatedAt;

    /**
     * Atualiza a data de criação antes da persistência inicial.
     */
    #[ORM\PrePersist ]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Atualiza a data de modificação antes de cada atualização.
     */
    #[ORM\PreUpdate ]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
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
     * @return self
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
     * @return self
     */
    public function setSlug( string $slug ): self
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     * @return self
     */
    public function setPrice( float $price ): self
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return bool
     */
    public function isStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     * @return self
     */
    public function setStatus( bool $status ): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxBudgets(): int
    {
        return $this->maxBudgets;
    }

    /**
     * @param int $maxBudgets
     * @return self
     */
    public function setMaxBudgets( int $maxBudgets ): self
    {
        $this->maxBudgets = $maxBudgets;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxClients(): int
    {
        return $this->maxClients;
    }

    /**
     * @param int $maxClients
     * @return self
     */
    public function setMaxClients( int $maxClients ): self
    {
        $this->maxClients = $maxClients;
        return $this;
    }

    /**
     * @return array<string>
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * @param array<string> $features
     * @return self
     */
    public function setFeatures( array $features ): self
    {
        $this->features = $features;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return self
     */
    public function setDescription( ?string $description ): self
    {
        $this->description = $description;
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
