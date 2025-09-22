<?php

namespace app\database\models;

use app\database\entitiesORM\UnitEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Unit extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'units';

    /**
     * Cria uma nova instância de UnitEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de UnitEntity.
     */
    protected static function createEntity(array $data): Entity
    {
        return UnitEntity::create($data);
    }

    public function findBySlug(string $slug): UnitEntity|Entity
    {
        try {
            return $this->findBy([
                'slug' => $slug,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar a unidade de medida, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
