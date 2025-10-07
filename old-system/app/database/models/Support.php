<?php

namespace app\database\models;

use app\database\entities\SupportEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Support extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'supports';

    protected static function createEntity(array $data): Entity
    {
        return SupportEntity::create($data);
    }

    public function getSupportByEmail(string $email): SupportEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'email' => $email ]);

            return $entity;
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar email, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getSupportById(int $id): SupportEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id ]);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar email de suporte, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

    public function getSupportByTenant_Id(int $tenant_id): SupportEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'tenant_id' => $tenant_id ]);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar email de suporte, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

}
