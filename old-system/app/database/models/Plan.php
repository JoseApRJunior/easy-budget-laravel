<?php

namespace app\database\models;

use app\database\entities\PlanEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

/**
 * Summary of Plan
 */
/**
 * Represents the Plan model, which extends the Model class.
 * The Plan model is responsible for managing plan-related data and operations.
 */
class Plan extends Model
{
    /**
     * Summary of table
     * @var string
     */
    protected string $table = 'plans';

    /**
     * Summary of createEntity
     * @param array <string,mixed> $data
     * @return \core\dbal\Entity
     */
    protected static function createEntity(array $data): Entity
    {
        return PlanEntity::create($data);
    }

    /**
     * Retrieves an active plan by its slug.
     *
     * @param string $slug The slug of the plan to retrieve.
     * @return array <string,mixed> | PlanEntity|Entity The active plan entity, or an exception if the plan is not found.
     */
    public function getActivePlanBySlug(string $slug): array|PlanEntity|Entity
    {
        $criteria = [
            'slug' => $slug,
            'status' => true,
        ];

        try {
            return $this->findBy($criteria);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o plano, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

    /**
     * Retrieves a list of active plans.
     *
     * @param int|null $limit The maximum number of plans to retrieve.
     * @param int|null $offset The number of plans to skip before retrieving.
     * @return array <string,mixed>|PlanEntity|Entity The list of active plans, or an exception if the retrieval fails.
     */
    public function findActivePlans(?int $limit = null, ?int $offset = null): array|PlanEntity|Entity
    {
        $criteria = [ 'status' => true ];
        $orderBy = [ 'created_at' => 'DESC' ];

        try {
            $entity = $this->findBy($criteria, $orderBy, $limit, $offset);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao listar os planos ativos, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

}
