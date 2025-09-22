<?php

namespace app\database\models;

use app\database\entitiesORM\BudgetStatusesEntity;
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

    /**
     * Cria uma nova instância de BudgetStatusesEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de BudgetStatusesEntity.
     */
    protected static function createEntity( array $data ): Entity
    {
        return BudgetStatusesEntity::create( $data );
    }

    public function getStatusById( int $id, int $tenant_id ): BudgetStatusesEntity|Entity
    {
        try {
            $entity = $this->findBy( [ 'id' => $id, 'tenant_id' => $tenant_id ] );

            return $entity;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o status, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    public function getStatusBySlug( string $slug ): BudgetStatusesEntity|Entity
    {
        try {
            $entity = $this->findBy( [ 'slug' => $slug ] );

            return $entity;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o status, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    /**
     * Busca todos os status de orçamento.
     *
     * @return array<int, Entity>|Entity Array com todos os status ou uma única entidade
     */
    public function getAllStatuses(): array|Entity
    {

        try {
            return $this->findAllByTenant();

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar os status, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }

    }

}
