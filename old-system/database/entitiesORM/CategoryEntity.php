<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use Doctrine\ORM\Mapping as ORM;

/**
 * Representa uma categoria no sistema.
 *
 * Esta entidade mapeia a tabela `categories` e armazena informações
 * sobre as categorias que podem ser associadas a serviços ou outras
 * entidades do sistema.
 *
 * Estrutura da tabela `categories`:
 * - id: INT(11) AUTO_INCREMENT PRIMARY KEY
 * - slug: VARCHAR(255) UNIQUE NOT NULL
 * - name: VARCHAR(255) NOT NULL
 * - created_at: DATETIME NOT NULL
 * - updated_at: DATETIME NULL
 *
 * @package app\database\entitiesORM
 */
#[ORM\Entity(repositoryClass: \app\database\repositories\CategoryRepository::class) ]
#[ORM\Table(name: 'categories') ]
#[ORM\HasLifecycleCallbacks ]
class CategoryEntity extends AbstractEntityORM
{
    /**
     * O identificador único da categoria.
     *
     * Campo da tabela: `id`
     * Tipo no banco: INT(11) AUTO_INCREMENT PRIMARY KEY
     *
     * @var int
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue(strategy: 'AUTO') ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * O ID do tenant ao qual esta categoria pertence.
     *
     * Campo da tabela: `tenant_id`
     * Tipo no banco: INTEGER NOT NULL
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false) ]
    private int $tenantId;

    /**
     * O slug único para a categoria, usado em URLs.
     *
     * Campo da tabela: `slug`
     * Tipo no banco: VARCHAR(255) UNIQUE NOT NULL
     *
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $slug;

    /**
     * O nome da categoria.
     *
     * Campo da tabela: `name`
     * Tipo no banco: VARCHAR(255) NOT NULL
     *
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $name;

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
     * Obtém o ID da categoria.
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
     * Obtém o slug da categoria.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Define o slug da categoria.
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
     * Obtém o nome da categoria.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Define o nome da categoria.
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
     * Serializa a entidade para JSON.
     *
     * Este método é usado para converter a entidade em um array
     * que pode ser serializado com segurança para JSON, evitando
     * problemas com proxies do Doctrine e referências circulares.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [ 
            'id'         => $this->id,
            'tenant_id'  => $this->tenantId,
            'slug'       => $this->slug ?? '',
            'name'       => $this->name ?? '',
            'created_at' => isset( $this->createdAt ) ? $this->createdAt->format( 'Y-m-d H:i:s' ) : null,
            'updated_at' => isset( $this->updatedAt ) ? $this->updatedAt->format( 'Y-m-d H:i:s' ) : null
        ];
    }

}