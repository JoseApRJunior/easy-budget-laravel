<?php

namespace app\database\repositories;

use App\Contracts\SlugAwareRepositoryInterface;
use app\database\entitiesORM\RoleEntity;
use app\interfaces\EntityORMInterface;
use Exception;
use RuntimeException;

/**
 * Repositório para gerenciar papéis do sistema.
 *
 * Estende AbstractNoTenantRepository para ter todos os métodos básicos sem tenant
 * implementados automaticamente, adicionando métodos específicos de papéis.
 *
 * @template T of RoleEntity
 * @extends AbstractNoTenantRepository<T>
 * @implements SlugAwareRepositoryInterface
 */
class RoleRepository extends AbstractNoTenantRepository implements SlugAwareRepositoryInterface
{
    // Métodos básicos da interface agora são herdados de AbstractNoTenantRepository

    /**
     * Verifica se existe um papel com o slug especificado.
     *
     * @param string $slug Slug a ser verificado
     * @param int|null $tenantId ID do tenant (ignorado para no-tenant)
     * @param int|null $excludeId ID a ser excluído (ignorado para no-tenant)
     * @return bool True se existe, false caso contrário
     */
    public function existsBySlug( string $slug, ?int $tenantId = null, ?int $excludeId = null ): bool
    {
        try {
            return $this->findBySlug( $slug ) !== null;
        } catch ( Exception $e ) {
            $this->logger->error( "Erro ao verificar existência de papel por slug '{$slug}': " . $e->getMessage() );
            return false;
        }
    }

    /**
     * Busca todos os papéis.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array<int, RoleEntity> Lista de papéis
     */
    public function findAllByTenant( array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        try {
            /** @var array<int, RoleEntity> $result */
            $result = $this->findBy( $criteria, $orderBy, $limit, $offset );
            return $result;
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar papéis, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    /**
     * Busca papéis com base em critérios específicos.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array<int, RoleEntity> Lista de papéis
     */
    public function findBy( array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        try {
            /** @var array<int, RoleEntity> $result */
            $result = parent::findBy( $criteria, $orderBy, $limit, $offset );
            return $result;
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar papéis com os critérios fornecidos, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

}
