<?php

namespace app\database\entitiesORM;

use Doctrine\ORM\Mapping as ORM;

/**
 * Representa dados comuns que podem ser compartilhados ou reutilizados.
 *
 * Esta entidade mapeia a tabela `common_datas` e armazena informações
 * como nomes, documentos (CPF/CNPJ) e detalhes de contato.
 *
 * @package app\database\entitiesORM
 */
#[ORM\Entity ]
#[ORM\Table(name: 'common_data') ]
#[ORM\HasLifecycleCallbacks ]
class CommonDataEntity extends AbstractEntityORM
{
    /**
     * O identificador único dos dados comuns.
     *
     * @var int
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue(strategy: 'AUTO') ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * O primeiro nome.
     *
     * @var string
     */
    #[ORM\Column(name: 'first_name', type: 'string', length: 255) ]
    private string $firstName;

    /**
     * O sobrenome.
     *
     * @var string
     */
    #[ORM\Column(name: 'last_name', type: 'string', length: 255) ]
    private string $lastName;

    /**
     * A data de nascimento.
     *
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'birth_date', type: 'date', nullable: true) ]
    private ?\DateTime $birthDate;

    /**
     * O CNPJ.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 18, nullable: true) ]
    private ?string $cnpj;

    /**
     * O CPF.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 14, nullable: true) ]
    private ?string $cpf;

    /**
     * O nome da empresa.
     *
     * @var string|null
     */
    #[ORM\Column(name: 'company_name', type: 'string', length: 255, nullable: true) ]
    private ?string $companyName;

    /**
     * A descrição.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true) ]
    private ?string $description;

    /**
     * A área de atividade.
     *
     * @var AreaOfActivityEntity|null
     */
    #[ORM\ManyToOne(targetEntity: AreaOfActivityEntity::class) ]
    #[ORM\JoinColumn(name: 'area_of_activity_id', referencedColumnName: 'id', nullable: true) ]
    private ?AreaOfActivityEntity $areaOfActivity;

    /**
     * A profissão.
     *
     * @var ProfessionEntity|null
     */
    #[ORM\ManyToOne(targetEntity: ProfessionEntity::class) ]
    #[ORM\JoinColumn(name: 'profession_id', referencedColumnName: 'id', nullable: true) ]
    private ?ProfessionEntity $profession;

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
     * Obtém o ID dos dados comuns.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtém o primeiro nome.
     *
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Define o primeiro nome.
     *
     * @param string $firstName
     * @return self
     */
    public function setFirstName( string $firstName ): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * Obtém o sobrenome.
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Define o sobrenome.
     *
     * @param string $lastName
     * @return self
     */
    public function setLastName( string $lastName ): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * Obtém a data de nascimento.
     *
     * @return \DateTime|null
     */
    public function getBirthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    /**
     * Define a data de nascimento.
     *
     * @param \DateTime|null $birthDate
     * @return self
     */
    public function setBirthDate( ?\DateTime $birthDate ): self
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    /**
     * Obtém o CNPJ.
     *
     * @return string|null
     */
    public function getCnpj(): ?string
    {
        return $this->cnpj;
    }

    /**
     * Define o CNPJ.
     *
     * @param string|null $cnpj
     * @return self
     */
    public function setCnpj( ?string $cnpj ): self
    {
        $this->cnpj = $cnpj;
        return $this;
    }

    /**
     * Obtém o CPF.
     *
     * @return string|null
     */
    public function getCpf(): ?string
    {
        return $this->cpf;
    }

    /**
     * Define o CPF.
     *
     * @param string|null $cpf
     * @return self
     */
    public function setCpf( ?string $cpf ): self
    {
        $this->cpf = $cpf;
        return $this;
    }

    /**
     * Obtém o nome da empresa.
     *
     * @return string|null
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * Define o nome da empresa.
     *
     * @param string|null $companyName
     * @return self
     */
    public function setCompanyName( ?string $companyName ): self
    {
        $this->companyName = $companyName;
        return $this;
    }

    /**
     * Obtém a descrição.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Define a descrição.
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
     * Obtém a área de atividade.
     *
     * @return AreaOfActivityEntity|null
     */
    public function getAreaOfActivity(): ?AreaOfActivityEntity
    {
        return $this->areaOfActivity;
    }

    /**
     * Define a área de atividade.
     *
     * @param AreaOfActivityEntity|null $areaOfActivity
     * @return self
     */
    public function setAreaOfActivity( ?AreaOfActivityEntity $areaOfActivity ): self
    {
        $this->areaOfActivity = $areaOfActivity;
        return $this;
    }

    /**
     * Obtém a profissão.
     *
     * @return ProfessionEntity|null
     */
    public function getProfession(): ?ProfessionEntity
    {
        return $this->profession;
    }

    /**
     * Define a profissão.
     *
     * @param ProfessionEntity|null $profession
     * @return self
     */
    public function setProfession( ?ProfessionEntity $profession ): self
    {
        $this->profession = $profession;
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