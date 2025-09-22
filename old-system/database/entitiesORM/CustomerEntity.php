<?php

declare(strict_types=1);

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Representa um cliente no sistema.
 *
 * Esta entidade mapeia a tabela `customers` e define as propriedades
 * e comportamentos de um cliente.
 *
 * @package app\database\entitiesORM
 */
#[ORM\Entity(repositoryClass: \app\database\repositories\CustomerRepository::class) ]
#[ORM\Table(name: 'customers') ]
#[ORM\HasLifecycleCallbacks ]
class CustomerEntity extends AbstractEntityORM
{
    /**
     * O identificador único do cliente.
     *
     * @var int
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue(strategy: 'AUTO') ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * Os dados comuns associados a este cliente (CPF/CNPJ, etc.).
     *
     * @var CommonDataEntity
     */
    #[ORM\OneToOne(targetEntity: CommonDataEntity::class, cascade: [ 'persist', 'remove' ]) ]
    #[ORM\JoinColumn(name: 'common_data_id', referencedColumnName: 'id', nullable: false) ]
    private CommonDataEntity $commonData;

    /**
     * As informações de contato deste cliente.
     *
     * @var ContactEntity
     */
    #[ORM\OneToOne(targetEntity: ContactEntity::class, cascade: [ 'persist', 'remove' ]) ]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id', nullable: true) ]
    private ?ContactEntity $contact = null;

    /**
     * O endereço deste cliente.
     *
     * @var AddressEntity
     */
    #[ORM\OneToOne(targetEntity: AddressEntity::class, cascade: [ 'persist', 'remove' ]) ]
    #[ORM\JoinColumn(name: 'address_id', referencedColumnName: 'id', nullable: true) ]
    private ?AddressEntity $address = null;

    /**
     * A coleção de orçamentos associados a este cliente.
     *
     * @var Collection<int, BudgetEntity>
     */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: BudgetEntity::class, fetch: 'LAZY') ]
    private Collection $budgets;

    /**
     * O status do cliente (ex: ativo, inativo).
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 10, options: [ 'default' => 'active' ]) ]
    private string $status = 'active';

    /**
     * A data de criação do registro.
     *
     * @var \DateTimeImmutable
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: false) ]
    private DateTimeImmutable $createdAt;

    /**
     * A data da última atualização do registro.
     *
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true) ]
    private ?DateTimeImmutable $updatedAt;

    /**
     * Construtor da entidade Cliente.
     * Inicializa a coleção de orçamentos.
     */
    public function __construct()
    {
        $this->budgets = new ArrayCollection();
    }

    /**
     * Executado antes da primeira persistência da entidade.
     * Define a data de criação.
     */
    #[ORM\PrePersist ]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * Executado antes de uma atualização na entidade.
     * Define a data da última atualização.
     */
    #[ORM\PreUpdate ]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Obtém o ID do cliente.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtém os dados comuns do cliente.
     *
     * @return CommonDataEntity
     */
    public function getCommonData(): CommonDataEntity
    {
        return $this->commonData;
    }

    /**
     * Define os dados comuns do cliente.
     *
     * @param CommonDataEntity $commonData
     * @return self
     */
    public function setCommonData( CommonDataEntity $commonData ): self
    {
        $this->commonData = $commonData;
        return $this;
    }

    /**
     * Obtém o contato do cliente.
     *
     * @return ?ContactEntity
     */
    public function getContact(): ?ContactEntity
    {
        return $this->contact;
    }

    /**
     * Define o contato do cliente.
     *
     * @param ?ContactEntity $contact
     * @return self
     */
    public function setContact( ?ContactEntity $contact ): self
    {
        $this->contact = $contact;
        return $this;
    }

    /**
     * Obtém o endereço do cliente.
     *
     * @return ?AddressEntity
     */
    public function getAddress(): ?AddressEntity
    {
        return $this->address;
    }

    /**
     * Define o endereço do cliente.
     *
     * @param ?AddressEntity $address
     * @return self
     */
    public function setAddress( ?AddressEntity $address ): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * Obtém a coleção de orçamentos do cliente.
     *
     * @return Collection<int, BudgetEntity>
     */
    public function getBudgets(): Collection
    {
        return $this->budgets;
    }

    /**
     * Adiciona um orçamento à coleção do cliente.
     *
     * @param BudgetEntity $budget
     * @return self
     */
    public function addBudget( BudgetEntity $budget ): self
    {
        if ( !$this->budgets->contains( $budget ) ) {
            $this->budgets[] = $budget;
            $budget->setCustomer( $this );
        }

        return $this;
    }

    /**
     * Remove um orçamento da coleção do cliente.
     *
     * @param BudgetEntity $budget
     * @return self
     */
    public function removeBudget( BudgetEntity $budget ): self
    {
        if ( $this->budgets->removeElement( $budget ) ) {
            // set the owning side to null (unless already changed)
            if ( $budget->getCustomer() === $this ) {
                $budget->setCustomer( null );
            }
        }

        return $this;
    }

    /**
     * Obtém o status do cliente.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Define o status do cliente.
     *
     * @param string $status
     * @return self
     */
    public function setStatus( string $status ): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Obtém a data de criação do registro.
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Obtém a data da última atualização do registro.
     *
     * @return \DateTimeImmutable|null
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

}