<?php

namespace app\database\repositories;

use app\database\entitiesORM\ProductEntity;

/**
 * Repositório para gerenciar produtos.
 *
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando métodos específicos de produtos.
 *
 * @template T of ProductEntity
 * @extends AbstractRepository<T>
 * @implements \App\Contracts\SlugAwareRepositoryInterface
 */
class ProductRepository extends AbstractRepository implements \App\Contracts\SlugAwareRepositoryInterface
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
     * Verifica se existe um produto com o slug especificado no tenant.
     *
     * @param string $slug Slug a ser verificado
     * @param int|null $tenantId ID do tenant (obrigatório para tenant-aware)
     * @param int|null $excludeId ID a ser excluído (para updates)
     * @return bool True se existe, false caso contrário
     * @throws \InvalidArgumentException Se tenantId não fornecido para tenant-aware
     */
    public function existsBySlug( string $slug, ?int $tenantId = null, ?int $excludeId = null ): bool
    {
        if ( $tenantId === null ) {
            throw new \InvalidArgumentException( 'tenantId é obrigatório para repositórios com tenant' );
        }

        return !$this->isSlugUniqueInTenant( $slug, $tenantId, $excludeId );
    }

}
