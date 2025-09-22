<?php

namespace app\database\models;

use app\database\entitiesORM\PlanEntity;
use app\database\Model;
use core\dbal\Entity;
use core\dbal\EntityNotFound;
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

    protected static function createEntity( array $data ): Entity
    {
        return PlanEntity::create( $data );
    }

    public function getActivePlanBySlug( string $slug ): PlanEntity|Entity
    {
        try {
            return $this->findBy( [ 
                'slug'   => $slug,
                'status' => true,
            ] );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o plano, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }

    }

    /**
     * Busca planos ativos.
     *
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Offset para paginação
     * @return array|Entity Array de planos ativos ou plano único
     */
    public function findActivePlans( ?int $limit = null, ?int $offset = null ): array|Entity
    {

        try {
            $entity = $this->findBy( [ 'status' => true ], [ 'created_at' => 'DESC' ], $limit, $offset );

            if ( $entity instanceof EntityNotFound ) {
                throw new RuntimeException( "Nenhum plano ativo encontrado." );
            }

            return $entity;
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao listar os planos ativos, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }

    }

}
