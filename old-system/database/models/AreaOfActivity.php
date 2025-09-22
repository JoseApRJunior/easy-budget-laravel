<?php

namespace app\database\models;

use app\database\entitiesORM\AreaOfActivityEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class AreaOfActivity extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'areas_of_activity';

    /**
     * Cria uma nova instância de AreaOfActivityEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de AreaOfActivityEntity.
     */
    protected static function createEntity(array $data): Entity
    {
        return AreaOfActivityEntity::create($data);
    }

    public function findBySlug(string $slug): AreaOfActivityEntity|Entity
    {
        try {
            return $this->findBy([
                'slug' => $slug,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar a área de atividade, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
