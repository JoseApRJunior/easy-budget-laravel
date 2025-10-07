<?php

namespace app\database\repositories;

use app\database\entitiesORM\ServiceStatusesEntity;
use app\interfaces\EntityORMInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Exception;

/**
 * Repository para gerenciar operações da entidade ServiceStatusesEntity
 *
 * Esta classe estende AbstractNoTenantRepository e fornece métodos para operações CRUD 
 * e consultas específicas relacionadas aos status de serviços no sistema.
 * 
 * @template T of ServiceStatusesEntity
 * @extends AbstractNoTenantRepository<T>
 */
class ServiceStatusesRepository extends AbstractNoTenantRepository
{
    /**
     * Persiste uma nova entidade ServiceStatuses no banco de dados
     *
     * @param ServiceStatusesEntity $serviceStatus A entidade ServiceStatus a ser persistida
     * @return EntityORMInterface|false Resultado da operação
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist(ServiceStatusesEntity $serviceStatus): EntityORMInterface|false
    {
        try {
            return $this->save($serviceStatus);
        } catch (ORMException | OptimisticLockException $e) {
            throw $e;
        }
    }

    /**
     * Atualiza uma entidade ServiceStatuses existente
     *
     * @param ServiceStatusesEntity $serviceStatus A entidade ServiceStatus a ser atualizada
     * @return EntityORMInterface Resultado da operação
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function update(ServiceStatusesEntity $serviceStatus): EntityORMInterface
    {
        try {
            $this->getEntityManager()->flush();
            return $serviceStatus;
        } catch (ORMException | OptimisticLockException $e) {
            throw $e;
        }
    }

    /**
     * Remove uma entidade ServiceStatuses do banco de dados
     *
     * @param ServiceStatusesEntity $serviceStatus A entidade ServiceStatus a ser removida
     * @return bool Resultado da operação
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(ServiceStatusesEntity $serviceStatus): bool
    {
        try {
            $id = $serviceStatus->getId();
            return $this->delete($id);
        } catch (ORMException | OptimisticLockException $e) {
            throw $e;
        }
    }

    /**
     * Busca status por slug
     *
     * @param string $slug Slug do status
     * @return ServiceStatusesEntity|null Entidade encontrada ou null
     */
    public function findBySlug(string $slug): ?ServiceStatusesEntity
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Busca todos os status ativos ordenados por orderIndex
     *
     * @return ServiceStatusesEntity[] Array de entidades ServiceStatus
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('ss')
            ->where('ss.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('ss.orderIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca todos os status organizados por slug
     *
     * @return array<string, ServiceStatusesEntity> Array com status organizados por slug
     */
    public function getAllStatusesBySlug(): array
    {
        $entities = $this->findAllActive();

        $statusesBySlug = [];
        foreach ($entities as $statusEntity) {
            $statusesBySlug[$statusEntity->getSlug()] = $statusEntity;
        }

        return $statusesBySlug;
    }

}