<?php

declare(strict_types=1);

namespace app\database\repositories;

use app\database\entitiesORM\ContactEntity;
use Doctrine\ORM\EntityRepository;

/**
 * Repositório para entidades ContactEntity.
 *
 *
 * @extends EntityRepository<ContactEntity>
 */
class ContactRepository extends EntityRepository
{
    /**
     * Busca contato por email e tenant ID.
     *
     * @param string $email Email do contato
     * @param int $tenantId ID do tenant
     * @return ContactEntity|null
     */
    public function findByEmail( string $email, int $tenantId ): ?ContactEntity
    {
        return $this->findOneBy( [ 
            'email'     => $email,
            'tenant_id' => $tenantId
        ] );
    }

}
