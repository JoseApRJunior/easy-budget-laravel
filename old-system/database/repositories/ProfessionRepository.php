<?php

declare(strict_types=1);

namespace app\database\repositories;

use app\database\entitiesORM\ProfessionEntity;
use Doctrine\ORM\EntityRepository;

/**
 * Repositório para gerenciar operações de endereços.
 *
 * Esta classe estende AbstractNoTenantRepository e fornece métodos
 * para buscar, criar, atualizar e deletar endereços no banco de dados usando Doctrine ORM.
 *
 * @template T of ProfessionEntity
 * @extends AbstractNoTenantRepository<T>
 * @package app\database\repositories
 */
class ProfessionRepository extends AbstractNoTenantRepository
{

}