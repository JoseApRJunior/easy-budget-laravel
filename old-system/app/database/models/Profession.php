<?php

namespace app\database\models;

use app\database\entities\ProfessionEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Profession extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'professions';

    /**
     * Creates a new ProfessionEntity instance from the provided data array.
     *
     * @param array $data The data to use for creating the entity.
     * @return Entity The created ProfessionEntity instance.
     */
    protected static function createEntity(array $data): Entity
    {
        return ProfessionEntity::create($data);
    }

    public function findBySlug(string $slug): ProfessionEntity|Entity
    {
        try {
            return $this->findBy([
                'slug' => $slug,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar a profissão, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
