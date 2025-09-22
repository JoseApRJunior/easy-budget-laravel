<?php

namespace app\database\entitiesORM;

use Doctrine\ORM\Mapping as ORM;

/**
 * Representa um endereço no sistema.
 *
 * Esta entidade mapeia a tabela `addresses` e armazena informações
 * detalhadas sobre endereços, como logradouro, cidade, estado e CEP.
 *
 * @package app\database\entitiesORM
 */
#[ORM\Entity ]
#[ORM\Table(name: 'addresses') ]
#[ORM\HasLifecycleCallbacks ]
class AddressEntity extends AbstractEntityORM
{
    /**
     * O identificador único do endereço.
     *
     * @var int
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue(strategy: 'AUTO') ]
    #[ORM\Column(name: 'id', type: 'integer') ]
    private ?int $id = null;

    /**
     * O logradouro do endereço.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true) ]
    private ?string $address;

    /**
     * O número do endereço.
     *
     * @var string|null
     */
    #[ORM\Column(name: 'address_number', type: 'string', length: 20, nullable: true) ]
    private ?string $addressNumber;

    /**
     * O bairro do endereço.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true) ]
    private ?string $neighborhood;

    /**
     * A cidade do endereço.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true) ]
    private ?string $city;

    /**
     * O estado do endereço (UF).
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 2, nullable: true) ]
    private ?string $state;

    /**
     * O CEP do endereço.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 9, nullable: true) ]
    private ?string $cep;

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
     * Obtém o ID do endereço.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtém o logradouro.
     *
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * Define o logradouro.
     *
     * @param string|null $address
     * @return self
     */
    public function setAddress( ?string $address ): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * Obtém o número do endereço.
     *
     * @return string|null
     */
    public function getAddressNumber(): ?string
    {
        return $this->addressNumber;
    }

    /**
     * Define o número do endereço.
     *
     * @param string|null $addressNumber
     * @return self
     */
    public function setAddressNumber( ?string $addressNumber ): self
    {
        $this->addressNumber = $addressNumber;
        return $this;
    }

    /**
     * Obtém o bairro.
     *
     * @return string|null
     */
    public function getNeighborhood(): ?string
    {
        return $this->neighborhood;
    }

    /**
     * Define o bairro.
     *
     * @param string|null $neighborhood
     * @return self
     */
    public function setNeighborhood( ?string $neighborhood ): self
    {
        $this->neighborhood = $neighborhood;
        return $this;
    }

    /**
     * Obtém a cidade.
     *
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Define a cidade.
     *
     * @param string|null $city
     * @return self
     */
    public function setCity( ?string $city ): self
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Obtém o estado (UF).
     *
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * Define o estado (UF).
     *
     * @param string|null $state
     * @return self
     */
    public function setState( ?string $state ): self
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Obtém o CEP.
     *
     * @return string|null
     */
    public function getCep(): ?string
    {
        return $this->cep;
    }

    /**
     * Define o CEP.
     *
     * @param string|null $cep
     * @return self
     */
    public function setCep( ?string $cep ): self
    {
        $this->cep = $cep;
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
