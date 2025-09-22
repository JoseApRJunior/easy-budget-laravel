<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use Doctrine\ORM\Mapping as ORM;

/**
 * Representa uma área de atividade no sistema.
 *
 * Esta entidade mapeia a tabela `areas_of_activity` e armazena informações
 * sobre as diferentes áreas de atuação que podem ser associadas a entidades.
 *
 * @package app\database\entitiesORM
 */
#[ORM\Entity ]
#[ORM\Table(name: 'area_of_activities') ]
#[ORM\HasLifecycleCallbacks ]
class AreaOfActivityEntity extends AbstractEntityORM
{
    /**
     * O identificador único da área de atividade.
     *
     * @var int
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue(strategy: 'AUTO') ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * O ID do tenant ao qual esta área de atividade pertence.
     *
     * Campo da tabela: `tenant_id`
     * Tipo no banco: INTEGER NOT NULL
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false) ]
    private int $tenantId;

    /**
     * O slug único para a área de atividade.
     *
     * @var string
     */
    #[ORM\Column(type: 'string', unique: true) ]
    private string $slug;

    /**
     * O nome da área de atividade.
     *
     * @var string
     */
    #[ORM\Column(type: 'string') ]
    private string $name;

    /**
     * Indica se a área de atividade está ativa.
     *
     * @var bool
     */
    #[ORM\Column(name: 'is_active', type: 'boolean') ]
    private bool $isActive = true;

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
     * Obtém o ID da área de atividade.
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
     * Obtém o slug da área de atividade.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Define o slug da área de atividade.
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
     * Obtém o nome da área de atividade.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Define o nome da área de atividade.
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
     * Verifica se a área de atividade está ativa.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Define se a área de atividade está ativa.
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