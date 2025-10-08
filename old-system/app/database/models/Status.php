<?php

namespace app\database\models;

use app\database\entities\StatusEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Status extends Model
{

    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'statuses';

    protected static function createEntity( array $data ): Entity
    {
        return StatusEntity::create( $data );
    }

    public function getStatusById( int $id, int $tenant_id ): StatusEntity|Entity
    {
        try {
            $entity = $this->findBy( [ 'id' => $id, 'tenant_id' => $tenant_id ] );

            return $entity;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o status, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    public function getAllStatuses( int $tenant_id ): array
    {

        try {
            $entities = $this->findBy( [ 'tenant_id' => $tenant_id ] );

            return $entities;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar os status, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }

    }

}