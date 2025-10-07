<?php

namespace app\database\models;

use app\database\entitiesORM\CategoryEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Category extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'categories';

    /**
     * Cria uma nova instância de CategoryEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de CategoryEntity.
     */
    protected static function createEntity( array $data ): Entity
    {
        return CategoryEntity::create( $data );
    }

    public function getCategoryById( int $id ): CategoryEntity|Entity
    {
        try {
            return $this->findBy( [ 'id' => $id ] );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar a categoria , tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    /**
     * Busca todas as categorias.
     *
     * @return array<int, Entity>|Entity Array com todas as categorias ou uma única entidade
     */
    public function getAllCategories(): array|Entity
    {

        try {
            return $this->findAllByTenant();
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar as categorias, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }

    }

}
