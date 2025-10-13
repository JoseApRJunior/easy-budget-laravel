<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório para gerenciamento de clientes.
 *
 * Estende AbstractTenantRepository para operações tenant-aware
 * com isolamento automático de dados por empresa.
 */
class CustomerRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Customer();
    }

    /**
     * Lista clientes ativos dentro do tenant atual.
     *
     * @param array<string, string>|null $orderBy
     * @param int|null $limit
     * @return Collection<Customer>
     */
    public function listActive( ?array $orderBy = null, ?int $limit = null ): Collection
    {
        return $this->getAllByTenant(
            [ 'status' => 'active' ],
            $orderBy,
            $limit,
        );
    }

    /**
     * Conta clientes dentro do tenant atual com filtros opcionais.
     *
     * @param array<string, mixed> $filters
     * @return int
     */
    public function countByFilters( array $filters = [] ): int
    {
        return $this->countByTenant( $filters );
    }

    /**
     * Verifica existência por critérios dentro do tenant atual.
     *
     * @param array<string, mixed> $criteria
     * @return bool
     */
    public function existsByCriteria( array $criteria ): bool
    {
        return $this->findByMultipleCriteria( $criteria )->isNotEmpty();
    }

    /**
     * Remove múltiplos clientes por IDs dentro do tenant atual.
     *
     * @param array<int> $ids
     * @return int Número de registros removidos
     */
    public function deleteManyByIds( array $ids ): int
    {
        return $this->deleteManyByTenant( $ids );
    }

    /**
     * Atualiza múltiplos registros por critérios dentro do tenant atual.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $updates
     * @return int Número de registros atualizados
     */
    public function updateManyByCriteria( array $criteria, array $updates ): int
    {
        $query = $this->model->newQuery();
        $this->applyFilters( $query, $criteria );
        return $query->update( $updates );
    }

    /**
     * Busca clientes por múltiplos critérios dentro do tenant atual.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return Collection<Customer>
     */
    public function findByCriteria(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        return $this->getAllByTenant( $criteria, $orderBy, $limit, $offset );
    }

    /**
     * Retorna clientes paginados dentro do tenant atual.
     *
     * @param int $perPage
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return LengthAwarePaginator
     */
    public function paginateByCriteria(
        int $perPage = 15,
        array $criteria = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        return $this->paginateByTenant( $perPage, $criteria, $orderBy );
    }

    /**
     * Lista clientes por filtros (compatibilidade com service).
     *
     * @param array<string, mixed> $filters
     * @param array<string, string>|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return Collection<Customer>
     */
    public function listByFilters(
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        return $this->getAllByTenant( $filters, $orderBy, $limit, $offset );
    }

}
