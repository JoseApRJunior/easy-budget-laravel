<?php

declare(strict_types=1);

namespace App\Services\Core\Traits;

use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Trait para fornecer isolamento de tenant em serviços.
 */
trait HasTenantIsolation
{
    /**
     * Garante que temos um tenant_id válido.
     * Prioriza o tenant_id do usuário autenticado.
     * 
     * @throws Exception Se não houver tenant_id disponível.
     */
    protected function ensureTenantId(?int $providedTenantId = null): int
    {
        $tenantId = $providedTenantId ?? Auth::user()?->tenant_id;

        if (!$tenantId) {
            throw new Exception('Operação requer um Tenant ID válido e nenhum foi fornecido ou encontrado no contexto.');
        }

        return (int) $tenantId;
    }
}
