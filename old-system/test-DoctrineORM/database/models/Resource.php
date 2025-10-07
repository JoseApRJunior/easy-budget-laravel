<?php

/* The PlanSubscription class extends the Model class and provides methods for managing plan subscriptions, including
checking provider plans and retrieving active subscriptions by provider and tenant. */

namespace app\database\models;

use app\database\entitiesORM\ResourceEntity;
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
     * Cria uma nova instância de ResourceEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de ResourceEntity.
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
