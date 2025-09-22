<?php

declare(strict_types=1);

namespace app\database\repositories;

use app\database\entitiesORM\InvoiceStatusesEntity;
use Doctrine\ORM\EntityRepository;

/**
 * Repositório para entidades InvoiceStatusesEntity.
 *
 * @extends EntityRepository<InvoiceStatusesEntity>
 */
class InvoiceStatusesRepository extends EntityRepository
{
    /**
     * Busca todos os status de fatura.
     *
     * @return array<InvoiceStatusesEntity>
     */
    public function getAllStatuses(): array
    {
        return $this->findAll();
    }

}