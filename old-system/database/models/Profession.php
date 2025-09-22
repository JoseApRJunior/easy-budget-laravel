<?php

namespace app\database\models;

use app\database\entitiesORM\ProfessionEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class Profession extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'professions';

    /**
     * Cria uma nova instância de ProfessionEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de ProfessionEntity.
     */
    protected static function createEntity(array $data): Entity
    {
        return ProfessionEntity::create($data);
    }

    public function findBySlug(string $slug): ProfessionEntity|Entity
    {
        try {
            return $this->findBy([
                'slug' => $slug,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar a profissão, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
