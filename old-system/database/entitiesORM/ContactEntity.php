<?php

namespace app\database\entitiesORM;

use Doctrine\ORM\Mapping as ORM;

/**
 * Representa a entidade de Contato no sistema.
 *
 * @package app\database\entitiesORM
 */
#[ORM\Entity ]
#[ORM\Table(name: 'contacts') ]
#[ORM\HasLifecycleCallbacks ]
class ContactEntity extends AbstractEntityORM
{
    /**
     * O identificador único do contato.
     *
     * @var int
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue(strategy: 'AUTO') ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * O e-mail principal do contato.
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255) ]
    private string $email;

    /**
     * O telefone principal do contato.
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 20) ]
    private string $phone;

    /**
     * O e-mail comercial do contato.
     *
     * @var string|null
     */
    #[ORM\Column(name: 'email_business', type: 'string', length: 255, nullable: true) ]
    private ?string $emailBusiness;

    /**
     * O telefone comercial do contato.
     *
     * @var string|null
     */
    #[ORM\Column(name: 'phone_business', type: 'string', length: 20, nullable: true) ]
    private ?string $phoneBusiness;

    /**
     * O website do contato.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true) ]
    private ?string $website;

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
     * Obtém o ID do contato.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtém o e-mail principal.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Define o e-mail principal.
     *
     * @param string $email
     * @return self
     */
    public function setEmail( string $email ): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Obtém o telefone principal.
     *
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * Define o telefone principal.
     *
     * @param string $phone
     * @return self
     */
    public function setPhone( string $phone ): self
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * Obtém o e-mail comercial.
     *
     * @return string|null
     */
    public function getEmailBusiness(): ?string
    {
        return $this->emailBusiness;
    }

    /**
     * Define o e-mail comercial.
     *
     * @param string|null $emailBusiness
     * @return self
     */
    public function setEmailBusiness( ?string $emailBusiness ): self
    {
        $this->emailBusiness = $emailBusiness;
        return $this;
    }

    /**
     * Obtém o telefone comercial.
     *
     * @return string|null
     */
    public function getPhoneBusiness(): ?string
    {
        return $this->phoneBusiness;
    }

    /**
     * Define o telefone comercial.
     *
     * @param string|null $phoneBusiness
     * @return self
     */
    public function setPhoneBusiness( ?string $phoneBusiness ): self
    {
        $this->phoneBusiness = $phoneBusiness;
        return $this;
    }

    /**
     * Obtém o website.
     *
     * @return string|null
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * Define o website.
     *
     * @param string|null $website
     * @return self
     */
    public function setWebsite( ?string $website ): self
    {
        $this->website = $website;
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
