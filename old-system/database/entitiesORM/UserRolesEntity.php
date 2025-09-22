<?php

namespace app\database\entitiesORM;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity ]
#[ORM\Table(name: 'user_roles') ]
class UserRolesEntity extends AbstractEntityORM
{
    #[ORM\Id ]
    #[ORM\ManyToOne(targetEntity: UserEntity::class) ]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id') ]
    private UserEntity $user;

    #[ORM\Id ]
    #[ORM\ManyToOne(targetEntity: RoleEntity::class) ]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id') ]
    private RoleEntity $role;

    #[ORM\Column(type: 'integer') ]
    private int $tenant_id;

    #[ORM\Column(name: 'created_at', type: 'datetime') ]
    private DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime') ]
    private DateTime $updatedAt;

    public function getUser(): UserEntity
    {
        return $this->user;
    }

    public function setUser( UserEntity $user ): void
    {
        $this->user = $user;
    }

    public function getRole(): RoleEntity
    {
        return $this->role;
    }

    public function setRole( RoleEntity $role ): void
    {
        $this->role = $role;
    }

    public function getTenantId(): int
    {
        return $this->tenant_id;
    }

    public function setTenantId( int $tenant_id ): void
    {
        $this->tenant_id = $tenant_id;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt( DateTime $createdAt ): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt( DateTime $updatedAt ): void
    {
        $this->updatedAt = $updatedAt;
    }

}
