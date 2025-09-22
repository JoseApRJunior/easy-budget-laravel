<?php

namespace app\database\entitiesORM;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade que representa uma permissão no sistema.
 *
 * Esta entidade mapeia a tabela `permissions` e define as permissões
 * que podem ser atribuídas aos roles no sistema de autorização.
 */
#[ORM\Entity ]
#[ORM\Table(name: 'permissions') ]
#[ORM\HasLifecycleCallbacks ]
class PermissionEntity extends AbstractEntityORM
{
    /**
     * @var int|null Identificador único da permissão
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * O ID do tenant ao qual esta permissão pertence.
     *
     * Campo da tabela: `tenant_id`
     * Tipo no banco: INTEGER NOT NULL
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false) ]
    private int $tenantId;

    /**
     * @var string Nome da permissão (ex: 'manage_users', 'view_reports')
     */
    #[ORM\Column(type: 'string', length: 100, nullable: false) ]
    private string $name;

    /**
     * @var string|null Descrição detalhada da permissão
     */
    #[ORM\Column(type: 'text', nullable: true) ]
    private ?string $description = null;

    /**
     * @var DateTimeImmutable Data de criação da permissão
     */
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable Data da última atualização da permissão
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $updatedAt;

    /**
     * Construtor da entidade PermissionEntity.
     *
     * @param string $name Nome da permissão
     * @param string|null $description Descrição da permissão
     */
    public function __construct( int $tenantId, string $name, ?string $description = null )
    {
        $this->tenantId    = $tenantId;
        $this->name        = $name;
        $this->description = $description;
    }

    /**
     * Callback executado antes de persistir a entidade.
     * Define automaticamente as datas de criação e atualização.
     */
    #[ORM\PrePersist ]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Callback executado antes de atualizar a entidade.
     * Atualiza automaticamente a data de modificação.
     */
    #[ORM\PreUpdate ]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Retorna o ID da permissão.
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
     * Retorna o nome da permissão.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Define o nome da permissão.
     *
     * @param string $name
     * @return $this
     */
    public function setName( string $name ): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Retorna a descrição da permissão.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Define a descrição da permissão.
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription( ?string $description ): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Retorna a data de criação da permissão.
     *
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Retorna a data da última atualização da permissão.
     *
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

}