<?php

namespace app\database\models;

use app\database\entitiesORM\AddressEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Address extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'addresses';

    protected static function createEntity(array $data): Entity
    {
        return AddressEntity::create($data);
    }

    /**
     * Retrieve an address by its ID and tenant ID.
     *
     * @param int $id The ID of the address.
     * @param int $tenant_id The ID of the tenant.
     * @return AddressEntity|Entity The address entity or a generic entity.
     */
    public function getAddressById(int $id, int $tenant_id): AddressEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar dados de endereço, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

}
