<?php

namespace app\database\repositories;

use app\database\entitiesORM\MerchantOrderMercadoPagoEntity;

/**
 * Repositório para gerenciar ordens de pagamento do Mercado Pago.
 * 
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando métodos específicos do Mercado Pago.
 *
 * @template T of MerchantOrderMercadoPagoEntity
 * @extends AbstractRepository<T>
 */
class MerchantOrderMercadoPagoRepository extends AbstractRepository
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
     * Busca ordem de pagamento por merchant_order_id e tenant.
     *
     * @param string $merchantOrderId ID da ordem no Mercado Pago
     * @param int $tenant_id ID do tenant
     * @return MerchantOrderMercadoPagoEntity|null Ordem encontrada ou null
     */
    public function findByMerchantOrderIdAndTenantId(string $merchantOrderId, int $tenant_id): ?MerchantOrderMercadoPagoEntity
    {
        /** @var MerchantOrderMercadoPagoEntity|null $result */
        $result = $this->findOneBy(['merchantOrderId' => $merchantOrderId, 'tenantId' => $tenant_id]);
        return $result;
    }
    
    /**
     * Busca ordens por status e tenant.
     *
     * @param string $status Status da ordem
     * @param int $tenant_id ID do tenant
     * @return array<int, MerchantOrderMercadoPagoEntity> Lista de ordens
     */
    public function findByStatusAndTenantId(string $status, int $tenant_id): array
    {
        return $this->findBy(['status' => $status, 'tenantId' => $tenant_id]);
    }
}

