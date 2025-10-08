<?php

namespace app\database\models;

use app\database\entitiesORM\TenantEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Tenant extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'tenants';

    protected static function createEntity(array $data): Entity
    {
        return TenantEntity::create($data);
    }

    public function getTenantByEmail(string $email): TenantEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'email' => $email ]);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar email, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

    public function getTenantById(string $id): TenantEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id ]);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

}
