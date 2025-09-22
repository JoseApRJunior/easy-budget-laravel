<?php

namespace app\database\models;

use app\database\entitiesORM\ServiceStatusesEntity;
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

    /**
     * Cria uma nova instância de ServiceStatusesEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de ServiceStatusesEntity.
     */
    protected static function createEntity( array $data ): Entity
    {
        return ServiceStatusesEntity::create( $data );
    }

    public function getStatusById( int $id, int $tenant_id ): ServiceStatusesEntity|Entity
    {
        try {
            $entity = $this->findBy( [ 'id' => $id, 'tenant_id' => $tenant_id ] );

            return $entity;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o status, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    public function getStatusBySlug( string $slug ): ServiceStatusesEntity|Entity
    {
        try {
            $entity = $this->findBy( [ 'slug' => $slug ] );

            return $entity;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o status, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    /**
     * Busca todos os status organizados por slug.
     *
     * @return array<string, ServiceStatusesEntity> Array com status organizados por slug
     */
    public function getAllStatusesBySlug(): array
    {
        try {
            $entities = $this->findAllByTenant();

            $statusesBySlug = [];
            if ( is_array( $entities ) ) {
                foreach ( $entities as $statusEntity ) {
                    if ( $statusEntity instanceof ServiceStatusesEntity ) {
                        $statusesBySlug[ $statusEntity->slug ] = $statusEntity;
                    }
                }
            } elseif ( $entities instanceof ServiceStatusesEntity ) {
                $statusesBySlug[ $entities->slug ] = $entities;
            }

            return $statusesBySlug;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar os status, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

}
