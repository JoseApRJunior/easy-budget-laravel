<?php

namespace app\database\repositories;

use App\Contracts\SlugAwareRepositoryInterface;

use app\database\entitiesORM\CategoryEntity;

/**
 * Repositório para gerenciar categorias.
 *
 * Estende AbstractNoTenantRepository para ter todos os métodos básicos sem tenant
 * implementados automaticamente.
 *
 * @template T of CategoryEntity
 * @extends AbstractNoTenantRepository<T>
 * @package app\database\repositoreis
 */
class CategoryRepository extends AbstractNoTenantRepository implements SlugAwareRepositoryInterface
{
    // Métodos obrigatórios já estão implementados na classe abstrata:
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

    // Nota: Se necessário, métodos específicos podem ser adicionados aqui

    /**
     * Verifica se existe uma categoria com o slug especificado.
     * Como é repositório no-tenant, ignora tenantId.
     *
     * @param string $slug O slug a ser verificado
     * @param int|null $tenantId Ignorado para no-tenant
     * @param int|null $excludeId ID a excluir da verificação
     * @return bool True se existe, false caso contrário
     */
    public function existsBySlug( string $slug, ?int $tenantId = null, ?int $excludeId = null ): bool
    {
        $entity = $this->findBySlug( $slug );
        if ( !$entity ) {
            return false;
        }

        if ( $excludeId !== null && $entity->getId() === $excludeId ) {
            return false;
        }

        return true;
    }

}
