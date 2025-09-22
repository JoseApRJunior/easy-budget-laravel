<?php

namespace app\database\entitiesORM;

use app\database\repositories\ProviderRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProviderRepository::class)
 * @ORM\Table(name="providers")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity(repositoryClass: ProviderRepository::class) ]
#[ORM\Table(name: 'providers') ]
#[ORM\HasLifecycleCallbacks ]
class ProviderEntity extends AbstractEntityORM
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue(strategy: 'AUTO') ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * @var TenantEntity
     * @ORM\ManyToOne(targetEntity=TenantEntity::class)
     * @ORM\JoinColumn(name="tenant_id", referencedColumnName="id", nullable=false)
     */
    #[ORM\ManyToOne(targetEntity: TenantEntity::class) ]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false) ]
    private TenantEntity $tenant;

    /**
     * @var UserEntity
     * @ORM\OneToOne(targetEntity=UserEntity::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    #[ORM\OneToOne(targetEntity: UserEntity::class, cascade: [ 'persist', 'remove' ]) ]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false) ]
    private UserEntity $user;

    /**
     * @var CommonDataEntity
     * @ORM\OneToOne(targetEntity=CommonDataEntity::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="common_data_id", referencedColumnName="id", nullable=false)
     */
    #[ORM\OneToOne(targetEntity: CommonDataEntity::class, cascade: [ 'persist', 'remove' ]) ]
    #[ORM\JoinColumn(name: 'common_data_id', referencedColumnName: 'id', nullable: false) ]
    private CommonDataEntity $commonData;

    /**
     * @var ContactEntity
     * @ORM\OneToOne(targetEntity=ContactEntity::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id", nullable=false)
     */
    #[ORM\OneToOne(targetEntity: ContactEntity::class, cascade: [ 'persist', 'remove' ]) ]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id', nullable: false) ]
    private ContactEntity $contact;

    /**
     * @var AddressEntity
     * @ORM\OneToOne(targetEntity=AddressEntity::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id", nullable=false)
     */
    #[ORM\OneToOne(targetEntity: AddressEntity::class, cascade: [ 'persist', 'remove' ]) ]
    #[ORM\JoinColumn(name: 'address_id', referencedColumnName: 'id', nullable: false) ]
    private AddressEntity $address;

    /**
     * @var bool
     * @ORM\Column(name="terms_accepted", type="boolean")
     */
    #[ORM\Column(name: 'terms_accepted', type: 'boolean') ]
    private bool $termsAccepted = false;

    /**
     * @var DateTimeImmutable
     * @ORM\Column(name="created_at", type="datetime_immutable")
     */
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable
     * @ORM\Column(name="updated_at", type="datetime_immutable")
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $updatedAt;

    /**
     * @param TenantEntity $tenant
     * @param UserEntity $user
     * @param CommonDataEntity $commonData
     * @param ContactEntity $contact
     * @param AddressEntity $address
     */
    public function __construct(
        TenantEntity $tenant,
        UserEntity $user,
        CommonDataEntity $commonData,
        ContactEntity $contact,
        AddressEntity $address,
    ) {
        $this->tenant     = $tenant;
        $this->user       = $user;
        $this->commonData = $commonData;
        $this->contact    = $contact;
        $this->address    = $address;
    }

    /**
     * @ORM\PrePersist
     */
    #[ORM\PrePersist ]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @ORM\PreUpdate
     */
    #[ORM\PreUpdate ]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return TenantEntity
     */
    public function getTenant(): TenantEntity
    {
        return $this->tenant;
    }

    /**
     * @param TenantEntity $tenant
     * @return $this
     */
    public function setTenant( TenantEntity $tenant ): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    /**
     * @return UserEntity
     */
    public function getUser(): UserEntity
    {
        return $this->user;
    }

    /**
     * @param UserEntity $user
     * @return $this
     */
    public function setUser( UserEntity $user ): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return CommonDataEntity
     */
    public function getCommonData(): CommonDataEntity
    {
        return $this->commonData;
    }

    /**
     * @param CommonDataEntity $commonData
     * @return $this
     */
    public function setCommonData( CommonDataEntity $commonData ): self
    {
        $this->commonData = $commonData;
        return $this;
    }

    /**
     * @return ContactEntity
     */
    public function getContact(): ContactEntity
    {
        return $this->contact;
    }

    /**
     * @param ContactEntity $contact
     * @return $this
     */
    public function setContact( ContactEntity $contact ): self
    {
        $this->contact = $contact;
        return $this;
    }

    /**
     * @return AddressEntity
     */
    public function getAddress(): AddressEntity
    {
        return $this->address;
    }

    /**
     * @param AddressEntity $address
     * @return $this
     */
    public function setAddress( AddressEntity $address ): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTermsAccepted(): bool
    {
        return $this->termsAccepted;
    }

    /**
     * @param bool $termsAccepted
     * @return $this
     */
    public function setTermsAccepted( bool $termsAccepted ): self
    {
        $this->termsAccepted = $termsAccepted;
        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

}
