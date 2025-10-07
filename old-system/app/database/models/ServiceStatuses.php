<?php

namespace app\database\models;

use app\database\entities\ServiceStatusesEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class ServiceStatuses extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'service_statuses';

    protected static function createEntity(array $data): Entity
    {
        return ServiceStatusesEntity::create($data);
    }

    public function getStatusById(int $id, int $tenant_id): ServiceStatusesEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o status, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getStatusBySlug(string $slug): ServiceStatusesEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'slug' => $slug ]);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o status, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getAllStatusesBySlug(): array
    {

        try {
            $entities = $this->findBy([]);

            $statusesBySlug = [];
            foreach ($entities as $statusEntity) {
                $statusesBySlug[ $statusEntity->slug ] = $statusEntity;
            }

            return $statusesBySlug;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os status, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

}
