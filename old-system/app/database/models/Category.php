<?php

namespace app\database\models;

use app\database\entities\CategoryEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Category extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'categories';

    protected static function createEntity(array $data): Entity
    {
        return CategoryEntity::create($data);
    }

    public function getCategoryById(int $id): CategoryEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id ]);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar a categoria , tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getAllCategories(): array
    {

        try {
            $entities = $this->findAll();

            return $entities;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar as categorias, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

}
