<?php

namespace app\database\repositories;

use app\database\entitiesORM\PaymentMercadoPagoInvoicesEntity;

/**
 * Repositório para gerenciar pagamentos de faturas via Mercado Pago.
 * 
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando métodos específicos de pagamentos.
 *
 * @template T of PaymentMercadoPagoInvoicesEntity
 * @extends AbstractRepository<T>
 */
class PaymentMercadoPagoInvoicesRepository extends AbstractRepository
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
     * Busca pagamentos por ID da fatura e tenant.
     *
     * @param int $invoiceId ID da fatura
     * @param int $tenant_id ID do tenant
     * @return array<int, PaymentMercadoPagoInvoicesEntity> Lista de pagamentos
     */
    public function findByInvoiceIdAndTenantId(int $invoiceId, int $tenant_id): array
    {
        return $this->findBy(['invoiceId' => $invoiceId, 'tenantId' => $tenant_id]);
    }
    
    /**
     * Busca pagamento por payment_id do Mercado Pago e tenant.
     *
     * @param string $paymentId ID do pagamento no Mercado Pago
     * @param int $tenant_id ID do tenant
     * @return PaymentMercadoPagoInvoicesEntity|null Pagamento encontrado ou null
     */
    public function findByPaymentIdAndTenantId(string $paymentId, int $tenant_id): ?PaymentMercadoPagoInvoicesEntity
    {
        /** @var PaymentMercadoPagoInvoicesEntity|null $result */
        $result = $this->findOneBy(['paymentId' => $paymentId, 'tenantId' => $tenant_id]);
        return $result;
    }
}

