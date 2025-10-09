<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface principal que combina funcionalidades básicas e avançadas
 *
 * Esta interface pode ser usada diretamente por repositories simples
 * ou estendida por interfaces mais específicas
 */
interface RepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca registros com múltiplos critérios e opções avançadas
     */
    public function findWithOptions( array $criteria, array $options = [] ): Collection;

    /**
     * Busca primeiro registro com critérios específicos
     */
    public function findOneBy( array $criteria ): ?Model;

    /**
     * Busca registros com ordenação específica
     */
    public function findByOrdered( array $criteria, array $orderBy = [] ): Collection;

    /**
     * Busca registros com eager loading de relacionamentos
     */
    public function findWithRelations( array $criteria, array $relations = [] ): Collection;

    /**
     * Executa operações em lote (batch operations)
     */
    public function batchUpdate( array $criteria, array $data ): int;

    /**
     * Busca registros com cache inteligente
     */
    public function findCached( array $criteria, int $ttl = 3600 ): Collection;

    /**
     * Limpa cache específico do repositório
     */
    public function clearCache(): void;
}
