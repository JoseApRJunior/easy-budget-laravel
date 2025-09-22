<?php

namespace app\database\entitiesORM;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade que representa a associação entre roles e permissões.
 *
 * Esta entidade mapeia a tabela `role_permissions` que estabelece
 * o relacionamento many-to-many entre roles e permissões.
 */
#[ORM\Entity ]
#[ORM\Table(name: 'role_permissions') ]
class RolePermissionEntity extends AbstractEntityORM
{
    /**
     * @var RoleEntity|null Role associado à permissão
     */
    #[ORM\Id ]
    #[ORM\ManyToOne(targetEntity: RoleEntity::class, inversedBy: 'rolePermissions') ]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: false) ]
    private ?RoleEntity $role = null;

    /**
     * ID do tenant ao qual a permissão pertence.
     *
     * @var int
     */
    #[ORM\Id ]
    #[ORM\Column(name: 'tenant_id', type: 'integer', nullable: false) ]
    private int $tenantId;

    /**
     * @var PermissionEntity Permissão associada ao role
     */
    #[ORM\Id ]
    #[ORM\ManyToOne(targetEntity: PermissionEntity::class) ]
    #[ORM\JoinColumn(name: 'permission_id', referencedColumnName: 'id', nullable: false) ]
    private PermissionEntity $permission;

    /**
     * Construtor da entidade RolePermissionEntity.
     *
     * @param RoleEntity|null $role Role a ser associado
     * @param PermissionEntity $permission Permissão a ser associada
     */
    public function __construct( ?RoleEntity $role, PermissionEntity $permission, int $tenantId )
    {
        $this->role       = $role;
        $this->permission = $permission;
        $this->tenantId   = $tenantId;
    }

    /**
     * Retorna o role associado.
     *
     * @return RoleEntity|null
     */
    public function getRole(): ?RoleEntity
    {
        return $this->role;
    }

    /**
     * Retorna o ID do tenant.
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
     * Define o role associado.
     *
     * @param RoleEntity|null $role
     * @return $this
     */
    public function setRole( ?RoleEntity $role ): self
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Retorna a permissão associada.
     *
     * @return PermissionEntity
     */
    public function getPermission(): PermissionEntity
    {
        return $this->permission;
    }

    /**
     * Define a permissão associada.
     *
     * @param PermissionEntity $permission
     * @return $this
     */
    public function setPermission( PermissionEntity $permission ): self
    {
        $this->permission = $permission;
        return $this;
    }

}