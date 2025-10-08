<?php

namespace app\database\models;

use app\database\entities\ContactEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Contact extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'contacts';

    protected static function createEntity(array $data): Entity
    {
        return ContactEntity::create($data);
    }

    public function getContactById(int $id, int $tenant_id): ContactEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os dados de contato, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

    public function getContactByEmail(string $email, int $tenant_id): ContactEntity|Entity
    {
        try {
            $entity = $this->findBy(criteria: [ 'email' => $email, 'tenant_id' => $tenant_id ]);

            return $entity;
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar email, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
