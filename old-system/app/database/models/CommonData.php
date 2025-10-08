<?php

namespace app\database\models;

use app\database\entities\CommonDataEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class CommonData extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'common_datas';

    protected static function createEntity(array $data): Entity
    {
        return CommonDataEntity::create($data);
    }

    /**
     * Retrieves common data by ID and tenant ID
     *
     * @param int $id The ID of the common data to retrieve
     * @param int $tenant_id The tenant ID associated with the common data
     * @return CommonDataEntity|Entity Returns a CommonDataEntity object if found, or an Entity object otherwise
     */
    public function getCommonDataById(int $id, int $tenant_id): CommonDataEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar dados, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

}
