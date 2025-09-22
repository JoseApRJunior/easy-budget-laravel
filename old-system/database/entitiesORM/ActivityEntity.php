<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use Doctrine\ORM\Mapping as ORM;

/**
 * Registra uma atividade no sistema.
 *
 * Esta entidade mapeia a tabela `activities` e é usada para registrar
 * ações importantes realizadas pelos usuários, como a criação ou atualização
 * de outras entidades.
 *
 * @package app\database\entitiesORM
 */
#[ORM\Entity ]
#[ORM\Table(name: 'activities') ]
#[ORM\HasLifecycleCallbacks ]
class ActivityEntity extends AbstractEntityORM
{
    /**
     * O identificador único da atividade.
     *
     * @var int
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue(strategy: 'AUTO') ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * O ID do tenant.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer') ]
    private int $tenantId;

    /**
     * O ID do usuário.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer') ]
    private int $userId;

    /**
     * O tipo de ação realizada (ex: 'create', 'update').
     *
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $actionType;

    /**
     * O tipo da entidade relacionada à atividade (ex: 'budget', 'customer').
     *
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $entityType;

    /**
     * O ID da entidade relacionada.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer') ]
    private int $entityId;

    /**
     * A descrição da atividade.
     *
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $description;

    /**
     * Metadados adicionais em formato JSON.
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true) ]
    private ?array $metadata;

    /**
     * A data de criação do registro.
     *
     * @var \DateTimeImmutable
     */
    #[ORM\Column(type: 'datetime_immutable') ]
    private \DateTimeImmutable $createdAt;

    /**
     * A data da última atualização do registro.
     *
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true) ]
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
     * Obtém o ID da atividade.
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
     * Obtém o ID do usuário.
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Define o ID do usuário.
     *
     * @param int $userId
     * @return self
     */
    public function setUserId( int $userId ): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Obtém o tipo de ação.
     *
     * @return string
     */
    public function getActionType(): string
    {
        return $this->actionType;
    }

    /**
     * Define o tipo de ação.
     *
     * @param string $actionType
     * @return self
     */
    public function setActionType( string $actionType ): self
    {
        $this->actionType = $actionType;
        return $this;
    }

    /**
     * Obtém o tipo da entidade.
     *
     * @return string
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Define o tipo da entidade.
     *
     * @param string $entityType
     * @return self
     */
    public function setEntityType( string $entityType ): self
    {
        $this->entityType = $entityType;
        return $this;
    }

    /**
     * Obtém o ID da entidade.
     *
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * Define o ID da entidade.
     *
     * @param int $entityId
     * @return self
     */
    public function setEntityId( int $entityId ): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    /**
     * Obtém a descrição.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Define a descrição.
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
     * Obtém os metadados.
     *
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * Define os metadados.
     *
     * @param array<string, mixed>|null $metadata
     * @return self
     */
    public function setMetadata( ?array $metadata ): self
    {
        $this->metadata = $metadata;
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
