<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

/**
 * Representa uma credencial de provedor no sistema.
 *
 * Esta entidade mapeia a tabela `provider_credentials` e define as propriedades
 * e comportamentos de uma credencial de provedor.
 */
#[ORM\Entity ]
#[ORM\Table(name: 'provider_credentials') ]
#[ORM\HasLifecycleCallbacks ]
class ProviderCredentialEntity extends AbstractEntityORM
{
    /**
     * @var int ID da credencial.
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * @var string Gateway de pagamento (ex: 'mercadopago').
     */
    #[ORM\Column(name: 'payment_gateway', type: 'string') ]
    private string $paymentGateway;

    /**
     * @var string Token de acesso criptografado.
     */
    #[ORM\Column(name: 'access_token_encrypted', type: 'string') ]
    private string $accessTokenEncrypted;

    /**
     * @var string Token de atualização criptografado.
     */
    #[ORM\Column(name: 'refresh_token_encrypted', type: 'string') ]
    private string $refreshTokenEncrypted;

    /**
     * @var string|null Chave pública do provedor.
     */
    #[ORM\Column(name: 'public_key', type: 'string', nullable: true) ]
    private ?string $publicKey;

    /**
     * @var string|null ID do usuário no gateway de pagamento.
     */
    #[ORM\Column(name: 'user_id_gateway', type: 'string', nullable: true) ]
    private ?string $userIdGateway;

    /**
     * @var int|null Tempo de expiração do token em segundos.
     */
    #[ORM\Column(name: 'expires_in', type: 'integer', nullable: true) ]
    private ?int $expiresIn;

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
     * @var ProviderEntity Provedor ao qual a credencial pertence.
     */
    #[ORM\ManyToOne(targetEntity: ProviderEntity::class) ]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', nullable: false) ]
    private ProviderEntity $provider;

    /**
     * @var TenantEntity Inquilino ao qual a credencial pertence.
     */
    #[ORM\ManyToOne(targetEntity: TenantEntity::class) ]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false) ]
    private TenantEntity $tenant;

    /**
     * @param ProviderEntity $provider
     * @param TenantEntity $tenant
     */
    public function __construct( ProviderEntity $provider, TenantEntity $tenant )
    {
        $this->provider = $provider;
        $this->tenant   = $tenant;
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

    public function getPaymentGateway(): string
    {
        return $this->paymentGateway;
    }

    public function setPaymentGateway( string $paymentGateway ): self
    {
        $this->paymentGateway = $paymentGateway;
        return $this;
    }

    public function getAccessTokenEncrypted(): string
    {
        return $this->accessTokenEncrypted;
    }

    public function setAccessTokenEncrypted( string $accessTokenEncrypted ): self
    {
        $this->accessTokenEncrypted = $accessTokenEncrypted;
        return $this;
    }

    public function getRefreshTokenEncrypted(): string
    {
        return $this->refreshTokenEncrypted;
    }

    public function setRefreshTokenEncrypted( string $refreshTokenEncrypted ): self
    {
        $this->refreshTokenEncrypted = $refreshTokenEncrypted;
        return $this;
    }

    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    public function setPublicKey( ?string $publicKey ): self
    {
        $this->publicKey = $publicKey;
        return $this;
    }

    public function getUserIdGateway(): ?string
    {
        return $this->userIdGateway;
    }

    public function setUserIdGateway( ?string $userIdGateway ): self
    {
        $this->userIdGateway = $userIdGateway;
        return $this;
    }

    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }

    public function setExpiresIn( ?int $expiresIn ): self
    {
        $this->expiresIn = $expiresIn;
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

    public function getProvider(): ProviderEntity
    {
        return $this->provider;
    }

    public function setProvider( ProviderEntity $provider ): self
    {
        $this->provider = $provider;
        return $this;
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

}
