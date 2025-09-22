<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

/**
 * Representa um produto no sistema.
 *
 * Esta entidade mapeia a tabela `products` e define as propriedades
 * e comportamentos de um produto.
 */
#[ORM\Entity(repositoryClass: \app\database\repositories\ProductRepository::class) ]
#[ORM\Table(name: 'products') ]
#[ORM\HasLifecycleCallbacks ]
class ProductEntity extends AbstractEntityORM
{
    /**
     * @var int ID do produto.
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * @var string Nome do produto.
     */
    #[ORM\Column(type: 'string') ]
    private string $name;

    /**
     * @var string|null Descrição detalhada do produto.
     */
    #[ORM\Column(type: 'string', nullable: true) ]
    private ?string $description;

    /**
     * @var float Preço do produto.
     */
    #[ORM\Column(type: 'float') ]
    private float $price;

    /**
     * @var bool Indica se o produto está ativo.
     */
    #[ORM\Column(type: 'boolean') ]
    private bool $active = false;

    /**
     * @var string|null Código de identificação do produto.
     */
    #[ORM\Column(type: 'string', nullable: true) ]
    private ?string $code;

    /**
     * @var string|null Caminho para a imagem do produto.
     */
    #[ORM\Column(type: 'string', nullable: true) ]
    private ?string $image;

    /**
     * @var DateTimeImmutable Data de criação do registro.
     */
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable|null Data da última atualização do registro.
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true) ]
    private ?DateTimeImmutable $updatedAt;

    /**
     * @var TenantEntity Inquilino ao qual o produto pertence.
     */
    #[ORM\ManyToOne(targetEntity: TenantEntity::class) ]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false) ]
    private TenantEntity $tenant;

    /**
     * @var CategoryEntity Categoria à qual o produto pertence.
     */
    #[ORM\ManyToOne(targetEntity: CategoryEntity::class) ]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false) ]
    private CategoryEntity $category;

    /**
     * @var UnitEntity Unidade de medida do produto.
     */
    #[ORM\ManyToOne(targetEntity: UnitEntity::class) ]
    #[ORM\JoinColumn(name: 'unit_id', referencedColumnName: 'id', nullable: false) ]
    private UnitEntity $unit;

    /**
     * @param TenantEntity $tenant
     * @param CategoryEntity $category
     * @param UnitEntity $unit
     */
    public function __construct( TenantEntity $tenant, CategoryEntity $category, UnitEntity $unit )
    {
        $this->tenant   = $tenant;
        $this->category = $category;
        $this->unit     = $unit;
    }

    /**
     * Atualiza a data de criação antes da persistência inicial.
     */
    #[ORM\PrePersist ]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * Atualiza a data de modificação antes de cada atualização.
     */
    #[ORM\PreUpdate ]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName( string $name ): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription( ?string $description ): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice( float $price ): self
    {
        $this->price = $price;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive( bool $active ): self
    {
        $this->active = $active;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode( ?string $code ): self
    {
        $this->code = $code;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage( ?string $image ): self
    {
        $this->image = $image;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getTenant(): TenantEntity
    {
        return $this->tenant;
    }

    public function setTenant( TenantEntity $tenant ): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    public function getCategory(): CategoryEntity
    {
        return $this->category;
    }

    public function setCategory( CategoryEntity $category ): self
    {
        $this->category = $category;
        return $this;
    }

    public function getUnit(): UnitEntity
    {
        return $this->unit;
    }

    public function setUnit( UnitEntity $unit ): self
    {
        $this->unit = $unit;
        return $this;
    }

}
