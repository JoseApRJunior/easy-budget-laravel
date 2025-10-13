<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Service;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para gerenciamento de serviços.
 *
 * Estende AbstractTenantRepository para operações tenant-aware
 * com isolamento automático de dados por empresa.
 */
class ServiceRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Service();
    }

    /**
     * Lista serviços por status dentro do tenant atual.
     *
     * @param array<string> $statuses Lista de status
     * @param array<string, string>|null $orderBy Ordenação
     * @param int|null $limit Limite de registros
     * @return Collection<Service> Coleção de serviços
     */
    public function listByStatuses( array $statuses, ?array $orderBy = null, ?int $limit = null ): Collection
    {
        return $this->getAllByTenant(
            [ 'status' => $statuses ],
            $orderBy,
            $limit,
        );
    }

    /**
     * Lista serviços por provider dentro do tenant atual.
     *
     * @param int $providerId ID do provider
     * @param array<string, string>|null $orderBy Ordenação
     * @return Collection<Service> Coleção de serviços
     */
    public function listByProviderId( int $providerId, ?array $orderBy = null ): Collection
    {
        return $this->getAllByTenant(
            [ 'provider_id' => $providerId ],
            $orderBy,
        );
    }

    /**
     * Conta serviços por status dentro do tenant atual.
     *
     * @param string $status Status dos serviços
     * @return int Número de serviços
     */
    public function countByStatus( string $status ): int
    {
        return $this->countByTenant( [ 'status' => $status ] );
    }

    /**
     * Busca serviços ativos dentro do tenant atual.
     *
     * @param array<string, string>|null $orderBy Ordenação
     * @return Collection<Service> Serviços ativos
     */
    public function findActive( ?array $orderBy = null ): Collection
    {
        return $this->getAllByTenant(
            [ 'status' => 'active' ],
            $orderBy,
        );
    }

}
