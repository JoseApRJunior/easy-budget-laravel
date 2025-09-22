<?php

declare(strict_types=1);

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use app\database\entitiesORM\BudgetStatusesEntity;
use app\database\entitiesORM\CustomerEntity;
use app\database\repositories\BudgetRepository;
use app\interfaces\EntityORMInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Representa a entidade Orçamento no sistema.
 *
 * Esta classe define o mapeamento da tabela `budgets` para um objeto PHP,
 * utilizando o Doctrine ORM. Ela inclui os relacionamentos com outras
 * entidades como Cliente, Status do Orçamento e Serviços.
 *
 * @package app\database\entitiesORM
 */
#[ORM\Entity(repositoryClass: BudgetRepository::class) ]
#[ORM\Table(name: 'budgets', uniqueConstraints: [ new \Doctrine\ORM\Mapping\UniqueConstraint( name: 'unique_tenant_code', columns: [ 'tenant_id', 'code' ] ) ]) ]
#[ORM\HasLifecycleCallbacks ]
class BudgetEntity extends AbstractEntityORM
{
    /**
     * O identificador único do orçamento.
     *
     * @var int
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue(strategy: 'AUTO') ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: false) ]
    private int $tenantId;

    /**
     * O código único para identificação do orçamento.
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false) ]
    private string $code;

    /**
     * A descrição detalhada do orçamento.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true) ]
    private ?string $description;

    /**
     * O valor total do orçamento.
     *
     * @var float|null
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true) ]
    private ?float $total;

    /**
     * Os anexos do orçamento (arquivos, imagens, etc.).
     *
     * Campo da tabela: `attachment`
     * Tipo no banco: JSON NULL
     *
     * @var array|null
     */
    #[ORM\Column(type: 'json', nullable: true) ]
    private ?array $attachment = null;

    /**
     * O histórico de alterações do orçamento.
     *
     * Campo da tabela: `history`
     * Tipo no banco: JSON NULL
     *
     * @var array|null
     */
    #[ORM\Column(type: 'json', nullable: true) ]
    private ?array $history = null;

    /**
     * A data de validade do orçamento.
     *
     * @var \DateTimeInterface|null
     */
    #[ORM\Column(type: 'datetime', nullable: true) ]
    private ?\DateTimeInterface $dueDate;

    /**
     * O cliente associado a este orçamento.
     *
     * @var CustomerEntity|null
     */
    #[ORM\ManyToOne(targetEntity: CustomerEntity::class, inversedBy: 'budgets') ]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: true) ]
    private ?CustomerEntity $customer = null;

    /**
     * O status atual do orçamento.
     *
     * @var BudgetStatusesEntity
     */
    #[ORM\ManyToOne(targetEntity: BudgetStatusesEntity::class) ]
    #[ORM\JoinColumn(name: 'budget_statuses_id', referencedColumnName: 'id', nullable: false) ]
    private BudgetStatusesEntity $budgetStatuses;

    /**
     * A coleção de serviços associados a este orçamento.
     *
     * @var Collection<int, ServiceEntity>
     */
    #[ORM\OneToMany(mappedBy: 'budget', targetEntity: ServiceEntity::class, cascade: [ 'persist', 'remove' ]) ]
    private Collection $services;

    /**
     * A data de criação do registro.
     *
     * @var \DateTimeImmutable
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: false) ]
    private \DateTimeImmutable $createdAt;

    /**
     * A data da última atualização do registro.
     *
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true) ]
    private ?\DateTimeImmutable $updatedAt;

    /**
     * Construtor da entidade Orçamento.
     * Inicializa a coleção de serviços.
     */
    public function __construct()
    {
        $this->services = new ArrayCollection();
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

    /**
     * Obtém o ID do orçamento.
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
     * @param int $tenantId O ID do tenant.
     * @return self
     */
    public function setTenantId( int $tenantId ): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Obtém o código do orçamento.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Define o código do orçamento.
     *
     * @param string $code O novo código.
     * @return self
     */
    public function setCode( string $code ): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Obtém a descrição do orçamento.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Define a descrição do orçamento.
     *
     * @param string|null $description A nova descrição.
     * @return self
     */
    public function setDescription( ?string $description ): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Obtém o valor total do orçamento.
     *
     * @return float|null
     */
    public function getTotal(): ?float
    {
        return $this->total;
    }

    /**
     * Obtém os anexos do orçamento.
     *
     * @return array|null
     */
    public function getAttachment(): ?array
    {
        return $this->attachment;
    }

    /**
     * Define os anexos do orçamento.
     *
     * @param array|null $attachment
     * @return self
     */
    public function setAttachment( ?array $attachment ): self
    {
        $this->attachment = $attachment;
        return $this;
    }

    /**
     * Obtém o histórico de alterações do orçamento.
     *
     * @return array|null
     */
    public function getHistory(): ?array
    {
        return $this->history;
    }

    /**
     * Define o histórico de alterações do orçamento.
     *
     * @param array|null $history
     * @return self
     */
    public function setHistory( ?array $history ): self
    {
        $this->history = $history;
        return $this;
    }

    /**
     * Define o valor total do orçamento.
     *
     * @param float|null $total O novo valor.
     * @return self
     */
    public function setAmount( ?float $total ): self
    {
        $this->total = $total;
        return $this;
    }

    /**
     * Obtém a data de validade do orçamento.
     *
     * @return \DateTimeInterface|null
     */
    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    /**
     * Define a data de validade do orçamento.
     *
     * @param \DateTimeInterface|null $dueDate A nova data de validade.
     * @return self
     */
    public function setDueDate( ?\DateTimeInterface $dueDate ): self
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    /**
     * Obtém o cliente associado ao orçamento.
     *
     * @return CustomerEntity|null
     */
    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    /**
     * Define o cliente associado ao orçamento.
     *
     * @param CustomerEntity|null $customer O cliente.
     * @return self
     */
    public function setCustomer( ?CustomerEntity $customer ): self
    {
        $this->customer = $customer;
        return $this;
    }

    /**
     * Obtém o status do orçamento.
     *
     * @return BudgetStatusesEntity
     */
    public function getBudgetStatuses(): BudgetStatusesEntity
    {
        return $this->budgetStatuses;
    }

    /**
     * Define o status do orçamento.
     *
     * @param BudgetStatusesEntity $budgetStatuses O novo status.
     * @return self
     */
    public function setBudgetStatuses( BudgetStatusesEntity $budgetStatuses ): self
    {
        $this->budgetStatuses = $budgetStatuses;
        return $this;
    }

    /**
     * Obtém a coleção de serviços associados ao orçamento.
     *
     * @return Collection<int, ServiceEntity>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    /**
     * Adiciona um serviço à coleção do orçamento.
     *
     * @param ServiceEntity $service O serviço a ser adicionado.
     * @return self
     */
    public function addService( ServiceEntity $service ): self
    {
        if ( !$this->services->contains( $service ) ) {
            $this->services[] = $service;
            $service->setBudget( $this );
        }

        return $this;
    }

    /**
     * Remove um serviço da coleção do orçamento.
     *
     * @param ServiceEntity $service O serviço a ser removido.
     * @return self
     */
    public function removeService( ServiceEntity $service ): self
    {
        if ( $this->services->removeElement( $service ) ) {

            if ( $service->getBudget() === $this ) {
                $service->setBudget( null );

            }
        }

        return $this;
    }

    /**
     * Obtém a data de criação do registro.
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Obtém a data da última atualização do registro.
     *
     * @return \DateTimeImmutable|null
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

}