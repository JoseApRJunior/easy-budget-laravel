<?php

/* The PlanSubscription class extends the Model class and provides methods for managing plan subscriptions, including
checking provider plans and retrieving active subscriptions by provider and tenant. */

namespace app\database\models;

use app\database\entities\ResourceEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Resource extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'resources';

    /**
     * Creates a new ResourceEntity instance from the provided data array.
     *
     * @param array $data The data to use for creating the entity.
     * @return Entity The created ResourceEntity instance.
     */
    protected static function createEntity(array $data): Entity
    {
        return ResourceEntity::create($data);
    }

    public function findBySlug(string $slug): ResourceEntity|Entity
    {
        try {
            return $this->findBy([
                'slug' => $slug,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o recurso, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
