<?php

namespace app\database\models;

use app\database\entities\ActivityEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Activity extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'activities';

    protected static function createEntity(array $data): Entity
    {
        return ActivityEntity::create($data);
    }

    /**
     * Retrieve an activities by its ID and tenant ID.
     *
     * @param int $id The ID of the activities.
     * @param int $tenant_id The ID of the tenant.
     * @return ActivityEntity|Entity The activities entity or a generic entity.
     */
    public function getActivitiesById(int $id, int $tenant_id): ActivityEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os dados de atividades, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

    public function getRecentActivities($tenantId, $limit = 5)
    {
        return $this->connection->createQueryBuilder()
            ->select(
                'a.id',
                'a.action_type',
                'a.entity_type',
                'a.entity_id',
                'a.description',
                'a.created_at',
                'concat( cd.first_name, " ", cd.last_name ) as user_name',
                'a.metadata',
            )
            ->from($this->table, 'a')
            ->join('a', 'providers', 'p', 'a.user_id = p.user_id and a.tenant_id = p.tenant_id')
            ->join('p', 'common_datas', 'cd', 'p.common_data_id = cd.id and p.tenant_id = cd.tenant_id')
            ->where('a.tenant_id = :tenant_id')
            ->setParameter('tenant_id', $tenantId)
            ->orderBy('a.created_at', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
    }

}
