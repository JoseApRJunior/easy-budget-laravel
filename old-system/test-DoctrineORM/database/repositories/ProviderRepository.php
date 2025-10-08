<?php

namespace app\database\repositories;

use App\Contracts\SlugAwareRepositoryInterface;

use app\database\entitiesORM\ProviderEntity;
use app\interfaces\EntityORMInterface;
use Exception;

/**
 * Repositório para gerenciar operações relacionadas à entidade Provider.
 *
 * Este repositório estende AbstractRepository e fornece
 * métodos padronizados para acesso e manipulação de dados dos providers,
 * seguindo o padrão arquitetural do projeto.
 *
 * @template T of ProviderEntity
 * @extends AbstractRepository<T>
 */
class ProviderRepository extends AbstractRepository implements SlugAwareRepositoryInterface
{
    // Métodos sobrescritos com joins customizados para entidade Provider:
    // - findByIdAndTenantId(): Carrega provider com user, commonData, contact, address, tenant
    // - findAllByTenantId(): Lista providers com relacionamentos carregados

    // Métodos auxiliares disponíveis da classe pai:
    // - save(EntityORMInterface $entity, int $tenant_id): EntityORMInterface
    // - deleteByIdAndTenantId(int $id, int $tenant_id): bool
    // - validateTenantOwnership(EntityORMInterface $entity, int $tenant_id): void (protegido)
    // - countByTenantId(int $tenant_id, array $criteria = []): int (protegido)
    // - existsByTenantId(int $id, int $tenant_id): bool (protegido)
    public function findProviderFullByUserId( int $userId, int $tenantId ): ?EntityORMInterface
    {
        return $this->createQueryBuilder( 'p' )
            ->select( 'p', 'u', 'cd', 'c', 'a' )
            ->join( 'p.user', 'u' )
            ->join( 'p.commonData', 'cd' )
            ->join( 'p.contact', 'c' )
            ->join( 'p.address', 'a' )
            ->where( 'p.user = :userId' )
            ->andWhere( 'p.tenant = :tenantId' )
            ->setParameter( 'userId', $userId )
            ->setParameter( 'tenantId', $tenantId )
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findProviderFullByEmail( string $email ): ?EntityORMInterface
    {
        return $this->createQueryBuilder( 'p' )
            ->select( 'p', 'u', 'cd', 'c', 'a' )
            ->join( 'p.user', 'u' )
            ->join( 'p.commonData', 'cd' )
            ->join( 'p.contact', 'c' )
            ->join( 'p.address', 'a' )
            ->where( 'u.email = :email' )
            ->setParameter( 'email', $email )
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca uma entidade Provider pelo seu ID e tenant.
     *
     * @param int $id ID da entidade Provider
     * @param int $tenant_id ID do tenant
     * @return EntityORMInterface|null Retorna a entidade encontrada ou null
     */
    public function findByIdAndTenantId( int $id, int $tenant_id ): ?EntityORMInterface
    {
        return $this->createQueryBuilder( 'p' )
            ->select( 'p', 'u', 'cd', 'c', 'a', 't' )
            ->join( 'p.user', 'u' )
            ->join( 'p.commonData', 'cd' )
            ->join( 'p.contact', 'c' )
            ->join( 'p.address', 'a' )
            ->join( 'p.tenant', 't' )
            ->where( 'p.id = :id' )
            ->andWhere( 'p.tenant = :tenantId' )
            ->setParameter( 'id', $id )
            ->setParameter( 'tenantId', $tenant_id )
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca todas as entidades Provider de um tenant.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $criteria Critérios adicionais de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, ?EntityORMInterface> Retorna um array de entidades Provider
     */
    public function findAllByTenantId( int $tenant_id, array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        $queryBuilder = $this->createQueryBuilder( 'p' )
            ->select( 'p', 'u', 'cd', 'c', 'a', 't' )
            ->join( 'p.user', 'u' )
            ->join( 'p.commonData', 'cd' )
            ->join( 'p.contact', 'c' )
            ->join( 'p.address', 'a' )
            ->join( 'p.tenant', 't' )
            ->where( 'p.tenant = :tenantId' )
            ->setParameter( 'tenantId', $tenant_id );

        // Aplicar critérios adicionais de busca
        foreach ( $criteria as $field => $value ) {
            if ( $value !== null ) {
                $queryBuilder->andWhere( "p.{$field} = :{$field}" )
                    ->setParameter( $field, $value );
            }
        }

        // Aplicar ordenação
        if ( $orderBy !== null ) {
            foreach ( $orderBy as $field => $direction ) {
                $queryBuilder->addOrderBy( "p.{$field}", $direction );
            }
        } else {
            // Ordenação padrão por data de criação (mais recentes primeiro)
            $queryBuilder->orderBy( 'p.createdAt', 'DESC' );
        }

        // Aplicar limite e offset
        if ( $limit !== null ) {
            $queryBuilder->setMaxResults( $limit );
        }
        if ( $offset !== null ) {
            $queryBuilder->setFirstResult( $offset );
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Verifica se existe um provider com o slug especificado no tenant.
     *
     * @param string $slug O slug a ser verificado
     * @param int|null $tenantId O ID do tenant (obrigatório para tenant-aware)
     * @param int|null $excludeId ID a excluir da verificação
     * @return bool True se existe, false caso contrário
     * @throws Exception Se tenantId for null
     */
    public function existsBySlug( string $slug, ?int $tenantId = null, ?int $excludeId = null ): bool
    {
        if ( $tenantId === null ) {
            throw new Exception( 'Tenant ID é obrigatório para repositórios tenant-aware.' );
        }

        return $this->isSlugUniqueInTenant( $slug, $tenantId, $excludeId );
    }

}
