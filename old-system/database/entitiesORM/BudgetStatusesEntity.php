<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use Doctrine\ORM\Mapping as ORM;

/**
 * Representa um status de orçamento no sistema.
 *
 * Esta entidade mapeia a tabela `budget_statuses` e define os diferentes
 * estados que um orçamento pode assumir, como "Pendente", "Aprovado" ou "Rejeitado".
 *
 * @package app\database\entitiesORM
 */
#[ORM\Entity(repositoryClass: \app\database\repositories\BudgetStatusesRepository::class) ]
#[ORM\Table(name: 'budget_statuses') ]
#[ORM\UniqueConstraint(name: "tenant_slug_unique", columns: ["tenant_id", "slug"])]
#[ORM\HasLifecycleCallbacks ]
class BudgetStatusesEntity extends AbstractEntityORM
{
    /**
     * O identificador único do status.
     *
     * @var int
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
     * O slug único para o status.
     *
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $slug;

    /**
     * O nome do status.
     *
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $name;

    /**
     * A descrição do status.
     *
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $description;

    /**
     * A cor associada ao status (ex: #FFFFFF).
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
     * O índice de ordenação do status.
     *
     * @var int
     */
    #[ORM\Column(name: 'order_index', type: 'integer') ]
    private int $orderIndex;

    /**
     * Indica se o status está ativo.
     *
     * @var bool
     */
    #[ORM\Column(name: 'is_active', type: 'boolean') ]
    private bool $isActive;

    /**
     * A data de criação do registro.
     *
     * @var \DateTimeImmutable
     */
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable') ]
    private \DateTimeImmutable $createdAt;

    /**
     * A data da última atualização do registro.
     *
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true) ]
    private ?\DateTimeImmutable $updatedAt;

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

    /**
     * Obtém o ID do status.
     *
     * @return int
     */
    public function getId(): int
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
     * Obtém a descrição do status.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Define a descrição do status.
     *
     * @param string $description
     * @return self
     */
    public function setDescription( string $description ): self
    {
        $this->description = $description;
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
     * Obtém o índice de ordenação.
     *
     * @return int
     */
    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }

    /**
     * Define o índice de ordenação.
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

}
