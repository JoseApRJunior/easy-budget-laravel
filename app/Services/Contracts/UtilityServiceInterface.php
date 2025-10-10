<?php
declare(strict_types=1);

namespace App\Services\Contracts;

use App\Support\ServiceResult;

/**
 * Interface UtilityServiceInterface
 *
 * Contrato para funcionalidades de infraestrutura, cache e monitoramento.
 */
interface UtilityServiceInterface
{
    /**
     * Verifica a saúde e disponibilidade do serviço.
     */
    public function healthCheck(): ServiceResult;

    /**
     * Obtém dados para cache com invalidação inteligente.
     */
    public function getCacheableData( string $key, int $ttl, array $params = [] ): ServiceResult;

    /**
     * Invalida cache específico do serviço.
     */
    public function invalidateCache( string $pattern = '*' ): ServiceResult;

    /**
     * Obtém metadados e estatísticas do serviço.
     */
    public function getMetadata(): ServiceResult;

    /**
     * Processa recursos externos de forma assíncrona.
     */
    public function processExternalResource( string $resourceId, array $options = [] ): ServiceResult;
}
