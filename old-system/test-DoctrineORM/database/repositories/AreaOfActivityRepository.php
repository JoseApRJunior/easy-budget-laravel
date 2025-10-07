<?php

declare(strict_types=1);

namespace app\database\repositories;

use app\database\entitiesORM\AreaOfActivityEntity;
use Doctrine\ORM\EntityRepository;

/**
 * Repositório para entidades AreaOfActivityEntity.
 *
 * @extends EntityRepository<AreaOfActivityEntity>
 */
class AreaOfActivityRepository extends EntityRepository
{
    /**
     * Busca todas as áreas de atividade.
     *
     * @return array<AreaOfActivityEntity>
     */
    public function findAll(): array
    {
        return $this->findAll();
    }

}