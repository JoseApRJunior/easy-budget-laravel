<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use Doctrine\ORM\Mapping as ORM;

/**
 * Representa a entidade de Status de Fatura no sistema.
 *
 * Esta classe define o mapeamento da tabela `invoice_statuses` para um objeto PHP,
 * utilizando o Doctrine ORM.
 *
 * @package app\database\entitiesORM
 */
#[ORM\Entity ]
#[ORM\Table(name: 'invoice_statuses') ]
#[ORM\HasLifecycleCallbacks ]
class InvoiceStatusesEntity extends AbstractEntityORM
{
    /**
     * O identificador único do status da fatura.
     *
     * @var int|null
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue(strategy: 'AUTO') ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * O ID do tenant ao qual este status pertence.
     *
     * Campo da tabela: `tenant_id`
     * Tipo no banco: INTEGER NOT NULL
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false) ]
    private int $tenantId;

    /**
     * O nome do status.
     *
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $name;

    /**
     * O slug único para o status.
     *
     * @var string
     */
    #[ORM\Column(type: 'string', unique: true) ]
    private string $slug;

    /**
     * A cor associada ao status.
     *
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $color;

    /**
     * O ícone associado ao status.
     *
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $icon;

    /**
     * A descrição do status.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: true) ]
    private ?string $description = null;

    /**
     * O índice de ordem do status.
     *
     * Campo da tabela: `order_index`
     * Tipo no banco: INTEGER NOT NULL
     *
     * @var int
     */
    #[ORM\Column(type: 'integer') ]
    private int $orderIndex;

    /**
     * Indica se o status está ativo.
     *
     * Campo da tabela: `is_active`
     * Tipo no banco: BOOLEAN DEFAULT TRUE
     *
     * @var bool
     */
    #[ORM\Column(name: 'is_active', type: 'boolean', options: [ "default" => true ]) ]
    private bool $isActive = true;

    /**
     * A data de criação do registro.
     *
     * Campo da tabela: `created_at`
     * Tipo no banco: DATETIME NOT NULL
     *
     * @var \DateTimeImmutable
     */
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable') ]
    private \DateTimeImmutable $createdAt;

    /**
     * A data da última atualização do registro.
     *
     * Campo da tabela: `updated_at`
     * Tipo no banco: DATETIME NULL
     *
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true) ]
    private ?\DateTimeImmutable $updatedAt;

    /**
     * Obtém o ID do status da fatura.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Obtém o ID do tenant.
     *
     * @return int
     */
    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    /**
     * Define o ID do tenant.
     *
     * @param int $tenantId
     * @return self
     */
    public function setTenantId( int $tenantId ): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Obtém o índice de ordem.
     *
     * @return int
     */
    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }

    /**
     * Define o índice de ordem.
     *
     * @param int $orderIndex
     * @return self
     */
    public function setOrderIndex( int $orderIndex ): self
    {
        $this->orderIndex = $orderIndex;
        return $this;
    }

    /**
     * Verifica se o status está ativo.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Define se o status está ativo.
     *
     * @param bool $isActive
     * @return self
     */
    public function setIsActive( bool $isActive ): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * Obtém a data de criação.
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Obtém a data da última atualização.
     *
     * @return \DateTimeImmutable|null
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Obtém o nome do status.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Define o nome do status.
     *
     * @param string $name
     * @return self
     */
    public function setName( string $name ): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Obtém o slug do status.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Define o slug do status.
     *
     * @param string $slug
     * @return self
     */
    public function setSlug( string $slug ): self
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Obtém a cor do status.
     *
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * Define a cor do status.
     *
     * @param string $color
     * @return self
     */
    public function setColor( string $color ): self
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Obtém o ícone do status.
     *
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Define o ícone do status.
     *
     * @param string $icon
     * @return self
     */
    public function setIcon( string $icon ): self
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Obtém a descrição do status.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Define a descrição do status.
     *
     * @param string|null $description
     * @return self
     */
    public function setDescription( ?string $description ): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Executado antes da primeira persistência da entidade.
     * Define a data de criação.
     */
    #[ORM\PrePersist ]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Executado antes de uma atualização na entidade.
     * Define a data da última atualização.
     */
    #[ORM\PreUpdate ]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

}
