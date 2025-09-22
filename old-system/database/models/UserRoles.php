<?php

namespace app\database\models;

use app\database\entitiesORM\UserRolesEntity;
use app\database\Model;
use core\dbal\Entity;
use core\dbal\EntityNotFound;
use Doctrine\DBAL\ParameterType;

class UserRoles extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'user_roles';

    protected static function createEntity(array $data): Entity
    {
        return UserRolesEntity::create($data);
    }

    public function getUserRolesByUserIdRoleId(int $user_id, int $role_id, int $tenant_id): UserRolesEntity|Entity
    {
        // Procura o UserRoles pelo user_id e role_id
        $selected = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->table)
            ->where('user_id = :user_id')
            ->andWhere('role_id = :role_id')
            ->andWhere('tenant_id = :tenant_id')
            ->setParameter('user_id', $user_id, ParameterType::INTEGER)
            ->setParameter('role_id', $role_id, ParameterType::INTEGER)
            ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAssociative();

        // Retorna o objeto UserRolesEntity correspondente ao user_id e role_id fornecido ou EntityNotFound .
        return UserRolesEntity::create($selected);
    }

}
