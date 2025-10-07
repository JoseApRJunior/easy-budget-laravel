<?php

namespace app\database\models;

use app\database\entities\UnitEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Unit extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'units';

    /**
     * Creates a new UnitEntity instance from the provided data array.
     *
     * @param array $data The data to use for creating the entity.
     * @return Entity The created UnitEntity instance.
     */
    protected static function createEntity(array $data): Entity
    {
        return UnitEntity::create($data);
    }

    public function findBySlug(string $slug): UnitEntity|Entity
    {
        try {
            return $this->findBy([
                'slug' => $slug,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar a unidade de medida, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
