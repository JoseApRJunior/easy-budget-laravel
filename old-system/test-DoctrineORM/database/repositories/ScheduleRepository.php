<?php

namespace app\database\repositories;

use app\database\entitiesORM\ScheduleEntity;
use app\interfaces\EntityORMInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Exception;

/**
 * Repository para gerenciar operações da entidade ScheduleEntity
 *
 * Esta classe estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, fornecendo métodos específicos para agendamentos.
 *
 * @template T of ScheduleEntity
 * @extends AbstractRepository<T>
 */
class ScheduleRepository extends AbstractRepository
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
     * Persiste uma nova entidade Schedule no banco de dados
     *
     * @param ScheduleEntity $schedule A entidade Schedule a ser persistida
     * @param int $tenantId ID do tenant
     * @return EntityORMInterface Resultado da operação
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist(ScheduleEntity $schedule, int $tenantId): EntityORMInterface
    {
        try {
            return $this->save($schedule, $tenantId);
        } catch (ORMException | OptimisticLockException $e) {
            throw $e;
        }
    }

    /**
     * Atualiza uma entidade Schedule existente
     *
     * @param ScheduleEntity $schedule A entidade Schedule a ser atualizada
     * @return EntityORMInterface Resultado da operação
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function update(ScheduleEntity $schedule): EntityORMInterface
    {
        try {
            $this->getEntityManager()->flush();
            return $schedule;
        } catch (ORMException | OptimisticLockException $e) {
            throw $e;
        }
    }

    /**
     * Remove uma entidade Schedule do banco de dados
     *
     * @param ScheduleEntity $schedule A entidade Schedule a ser removida
     * @param int $tenantId ID do tenant
     * @return bool Resultado da operação
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(ScheduleEntity $schedule, int $tenantId): bool
    {
        try {
            $id = $schedule->getId();
            return $this->deleteByIdAndTenantId($id, $tenantId);
        } catch (ORMException | OptimisticLockException $e) {
            throw $e;
        }
    }

    /**
     * Busca agendamentos por tenant
     *
     * @param int $tenantId ID do tenant
     * @return ScheduleEntity[] Array de entidades Schedule
     */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.tenant = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca agendamentos por serviço
     *
     * @param int $serviceId ID do serviço
     * @return ScheduleEntity[] Array de entidades Schedule
     */
    public function findByService(int $serviceId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.service = :serviceId')
            ->setParameter('serviceId', $serviceId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

}