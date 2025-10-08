<?php

namespace app\database\models;

use app\database\entities\BudgetStatusesEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class BudgetStatuses extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'budget_statuses';

    protected static function createEntity(array $data): Entity
    {
        return BudgetStatusesEntity::create($data);
    }

    public function getStatusById(int $id, int $tenant_id): BudgetStatusesEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o status, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getStatusBySlug(string $slug): BudgetStatusesEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'slug' => $slug ]);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o status, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getAllStatuses(): array
    {

        try {
            $entities = $this->findBy([]);

            return $entities;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os status, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

}
