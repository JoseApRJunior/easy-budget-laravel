<?php

namespace app\database\repositories;

use app\database\entitiesORM\UserConfirmationTokenEntity;
use app\interfaces\EntityORMInterface;
use core\dbal\EntityNotFound;
use Exception;

/**
 * Repositório para gerenciar tokens de confirmação de usuários.
 * 
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando métodos específicos de tokens.
 *
 * @template T of UserConfirmationTokenEntity
 * @extends AbstractRepository<T>
 */
class UserConfirmationTokenRepository extends AbstractRepository
{
    // Todos os métodos obrigatórios já estão implementados na classe abstrata:
    // - findByIdAndTenantId(int $id, int $tenant_id): ?EntityORMInterface
    // - findAllByTenantId(int $tenant_id, array $criteria = []): array
    // - save(EntityORMInterface $entity, int $tenant_id): EntityORMInterface
    // - deleteByIdAndTenantId(int $id, int $tenant_id): bool
    
    // Métodos auxiliares disponíveis da classe pai:
    // - findBySlugAndTenantId(string $slug, int $tenant_id): ?EntityORMInterface (protegido)
    // - findActiveByTenantId(int $tenant_id): array (protegido)
    // - countByTenantId(int $tenant_id, array $criteria = []): int (protegido)
    // - existsByTenantId(int $id, int $tenant_id): bool (protegido)
    // - validateTenantOwnership(EntityORMInterface $entity, int $tenant_id): void (protegido)
    // - isSlugUniqueInTenant(string $slug, int $tenant_id, ?int $excludeId = null): bool (protegido)

    /**
     * Busca token por valor do token.
     *
     * @param string $token Valor do token
     * @return UserConfirmationTokenEntity|null Entidade encontrada ou null
     */
    public function findByToken(string $token): ?UserConfirmationTokenEntity
    {
        try {
            return $this->findOneBy(['token' => $token]);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Busca tokens por usuário.
     *
     * @param int $userId ID do usuário
     * @param int $tenantId ID do tenant
     * @return UserConfirmationTokenEntity|null Entidade encontrada ou null
     */
    public function findByUser(int $userId, int $tenantId): ?UserConfirmationTokenEntity
    {
        try {
            return $this->findOneBy(['user_id' => $userId, 'tenant_id' => $tenantId]);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Busca token por valor do token (compatibilidade com SharedService legado).
     *
     * @param string $token Valor do token
     * @return UserConfirmationTokenEntity|EntityNotFound Entidade encontrada ou EntityNotFound
     */
    public function getByToken(string $token): UserConfirmationTokenEntity|EntityNotFound
    {
        try {
            $entity = $this->findOneBy(['token' => $token]);
            
            if ($entity === null) {
                return new EntityNotFound();
            }
            
            return $entity;
        } catch (Exception $e) {
            return new EntityNotFound();
        }
    }

    /**
     * Cria um novo token de confirmação.
     *
     * @param EntityORMInterface $entity Entidade a ser criada
     * @param int $tenantId ID do tenant
     * @return EntityORMInterface Resultado da operação
     */
    public function create(EntityORMInterface $entity, int $tenantId): EntityORMInterface
    {
        return $this->save($entity, $tenantId);
    }

}