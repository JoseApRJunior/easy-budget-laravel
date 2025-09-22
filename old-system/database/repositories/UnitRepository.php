<?php

namespace app\database\repositories;

use app\database\entitiesORM\UnitEntity;
use app\interfaces\EntityORMInterface;
use Exception;
use RuntimeException;

/**
 * Repositório para gerenciar unidades do sistema.
 * 
 * Estende AbstractNoTenantRepository para ter todos os métodos básicos sem tenant
 * implementados automaticamente, adicionando métodos específicos de unidades.
 *
 * @template T of UnitEntity
 * @extends AbstractNoTenantRepository<T>
 */
class UnitRepository extends AbstractNoTenantRepository
{
    // Todos os métodos obrigatórios já estão implementados na classe abstrata:
    // - findById(int $id): ?EntityORMInterface
    // - findBy(array $criteria, ...): array
    // - findAll(array $criteria = [], ...): array
    // - save(EntityORMInterface $entity): EntityORMInterface|false
    // - delete(int $id): bool
    
    // Métodos auxiliares disponíveis da classe pai:
    // - findBySlug(string $slug): ?EntityORMInterface (protegido)
    // - findActive(): array (protegido)
    // - count(array $criteria = []): int (público)
    // - exists(int $id): bool (protegido)

    /**
     * Busca unidades ativas do sistema.
     *
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, EntityORMInterface>
     */
    public function findActiveUnits(?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->findActive($orderBy, $limit, $offset);
    }

    /**
     * Busca unidade por slug.
     *
     * @param string $slug Slug da unidade
     * @return EntityORMInterface|null
     */
    public function findUnitBySlug(string $slug): ?EntityORMInterface
    {
        return $this->findBySlug($slug);
    }
}

