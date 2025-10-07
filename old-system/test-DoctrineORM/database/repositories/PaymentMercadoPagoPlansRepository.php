<?php

namespace app\database\repositories;

use app\database\entitiesORM\PaymentMercadoPagoPlansEntity;

/**
 * Repositório para gerenciar pagamentos de planos via Mercado Pago.
 * 
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando métodos específicos de planos.
 *
 * @template T of PaymentMercadoPagoPlansEntity
 * @extends AbstractRepository<T>
 */
class PaymentMercadoPagoPlansRepository extends AbstractRepository
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
     * Busca pagamentos por subscription_id e tenant.
     *
     * @param int $subscriptionId ID da assinatura
     * @param int $tenant_id ID do tenant
     * @return array<int, PaymentMercadoPagoPlansEntity> Lista de pagamentos
     */
    public function findBySubscriptionIdAndTenantId(int $subscriptionId, int $tenant_id): array
    {
        return $this->findBy(['planSubscriptionId' => $subscriptionId, 'tenantId' => $tenant_id]);
    }
    
    /**
     * Busca pagamentos ativos por tenant.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, PaymentMercadoPagoPlansEntity> Lista de pagamentos ativos
     */
    public function findActiveByTenantId(int $tenant_id, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        // Usar ordenação padrão se não especificada
        $orderBy = $orderBy ?? ['createdAt' => 'DESC'];
        return $this->findBy(['status' => 'approved', 'tenantId' => $tenant_id], $orderBy, $limit, $offset);
    }
}

