<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Interface para Repositório de Pagamentos.
 */
interface PaymentRepositoryInterface extends TenantRepositoryInterface
{
    /**
     * Busca pagamentos filtrados por tenant.
     */
    public function getFilteredPayments(int $tenantId, array $filters = []);
}
