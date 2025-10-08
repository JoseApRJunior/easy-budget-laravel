<?php

namespace app\database\models;

use app\database\entities\UserEntity;
use app\database\Model;
use core\dbal\Entity;
use Doctrine\DBAL\ParameterType;
use Exception;
use RuntimeException;

class User extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'users';

    protected static function createEntity(array $data): Entity
    {
        return UserEntity::create($data);
    }

    public function getUserByEmail(string $email): UserEntity|Entity
    {
        try {
            $fields = array_keys(get_class_vars(UserEntity::class));
            $fields = array_diff($fields, [ 'password' ]);
            $entity = $this->findBy(fields: $fields, criteria: [ 'email' => $email ]);

            return $entity;
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar email, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getUserByEmailWithPassword(string $email): UserEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'email' => $email ]);

            return $entity;
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar email, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getUserById(int $id, int $tenant_id): UserEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

    public function getUserActive(string $email): UserEntity|Entity
    {

        try {
            $entity = $this->findBy([ 'email' => $email, 'is_active' => false ]);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

    public function getUserRoles(int $user_id, int $tenant_id): array
    {

        try {
            $result = $this->connection->createQueryBuilder()
                ->select('r.name')
                ->from('user_roles', 'ur')
                ->join('ur', 'roles', 'r', 'ur.role_id = r.id')
                ->where('ur.user_id = :user_id')
                ->andWhere('ur.tenant_id = :tenant_id')
                ->setParameter('user_id', $user_id, ParameterType::INTEGER)
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->executeQuery()
                ->fetchAllAssociative();

            return array_column($result, 'name');

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar as funções do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

    public function getUserPermissions(int $user_id, int $tenant_id): array
    {

        try {
            $result = $this->connection->createQueryBuilder()
                ->select('*')
                ->from('user_roles', 'ur')
                ->join('ur', 'role_permissions', 'rp', 'ur.role_id = rp.role_id')
                ->join('rp', 'permissions', 'p', 'rp.permission_id = p.id')
                ->where('ur.user_id = :user_id')
                ->andWhere('ur.tenant_id = :tenant_id')
                ->setParameter('user_id', $user_id, ParameterType::INTEGER)
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->executeQuery()
                ->fetchAllAssociative();

            return array_column($result, 'name');

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar as permissões do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
