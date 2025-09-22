<?php

namespace app\database\repositories;

use app\database\entitiesORM\CommonDataEntity;

/**
 * Repositório para gerenciar dados comuns.
 *
 * Estende AbstractNoTenantRepository para ter todos os métodos básicos sem tenant
 * implementados automaticamente, adicionando métodos específicos de dados comuns.
 *
 * @template T of CommonDataEntity
 * @extends AbstractNoTenantRepository<T>
 */
class CommonDataRepository extends AbstractNoTenantRepository
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
    // Métodos básicos da interface agora são herdados de AbstractNoTenantRepository
}