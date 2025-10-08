<?php

namespace app\database\repositories;

use app\database\entitiesORM\UserRolesEntity;
use app\interfaces\EntityORMInterface;
use Exception;

/**
 * Repositório para gerenciar papéis de usuários.
 *
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando métodos específicos de papéis.
 *
 * @template T of UserRolesEntity
 * @extends AbstractRepository<T>
 */
class UserRolesRepository extends AbstractRepository
{
    // Métodos básicos da interface agora são herdados de AbstractRepository

    /**
     * Busca papéis por usuário e tenant.
     *
     * @param int $userId ID do usuário
     * @param int $tenantId ID do tenant
     * @return array<int, EntityORMInterface> Lista de papéis do usuário
     */
    public function findByUserAndTenant( int $userId, int $tenantId ): array
    {
        try {
            return $this->findBy( [ 'user_id' => $userId, 'tenant_id' => $tenantId ] );
        } catch ( Exception $e ) {
            return [];
        }
    }

}
