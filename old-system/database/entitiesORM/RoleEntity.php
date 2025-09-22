<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="roles")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity ]
#[ORM\Table(name: 'roles') ]
#[ORM\HasLifecycleCallbacks ]
class RoleEntity extends AbstractEntityORM
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * ID do tenant ao qual o role pertence.
     *
     * @var int|null
     */
    #[ORM\Column(name: 'tenant_id', type: 'integer', nullable: false) ]
    private ?int $tenantId = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", unique=true, nullable=true)
     */
    #[ORM\Column(type: 'string', unique: true, nullable: true) ]
    private ?string $slug = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[ORM\Column(type: 'string', nullable: true) ]
    private ?string $name = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    #[ORM\Column(type: 'text', nullable: true) ]
    private ?string $description = null;

    /**
     * @var bool
     * @ORM\Column(name="is_active", type="boolean")
     */
    #[ORM\Column(name: 'is_active', type: 'boolean') ]
    private bool $isActive = true;

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
     * @var Collection<int, RolePermissionEntity> Coleção de associações role-permission
     */
    #[ORM\OneToMany(mappedBy: 'role', targetEntity: RolePermissionEntity::class, cascade: [ 'persist', 'remove' ]) ]
    private Collection $rolePermissions;

    /**
     * Construtor da entidade Role.
     *
     * @param string|null $slug
     * @param string|null $name
     */
    public function __construct( ?string $slug = null, ?string $name = null )
    {
        if ( $slug !== null ) {
            $this->slug = $slug;
        }
        if ( $name !== null ) {
            $this->name = $name;
        }
        $this->rolePermissions = new ArrayCollection();
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
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string|null $slug
     * @return $this
     */
    public function setSlug( ?string $slug ): self
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName( ?string $name ): self
    {
        $this->name = $name;
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

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return $this
     */
    public function setDescription( ?string $description ): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive( bool $isActive ): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * Retorna a coleção de associações role-permission.
     *
     * @return Collection<int, RolePermissionEntity>
     */
    public function getRolePermissions(): Collection
    {
        return $this->rolePermissions;
    }

    /**
     * Adiciona uma associação role-permission.
     *
     * @param RolePermissionEntity $rolePermission
     * @return $this
     */
    public function addRolePermission( RolePermissionEntity $rolePermission ): self
    {
        if ( !$this->rolePermissions->contains( $rolePermission ) ) {
            $this->rolePermissions->add( $rolePermission );
            $rolePermission->setRole( $this );
        }

        return $this;
    }

    /**
     * Remove uma associação role-permission.
     *
     * @param RolePermissionEntity $rolePermission
     * @return $this
     */
    public function removeRolePermission( RolePermissionEntity $rolePermission ): self
    {
        if ( $this->rolePermissions->removeElement( $rolePermission ) ) {
            // Define o lado proprietário como null (a menos que já tenha sido alterado)
            if ( $rolePermission->getRole() === $this ) {
                $rolePermission->setRole( null );
            }
        }

        return $this;
    }

}