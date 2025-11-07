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
     * Conta serviços agrupados por status dentro do tenant atual.
     *
     * @return array<string, int> Array com status como chave e count como valor
     */
    public function countByStatus(): array
    {
        return $this->model
            ->selectRaw( 'status, COUNT(*) as count' )
            ->groupBy( 'status' )
            ->pluck( 'count', 'status' )
            ->toArray();
    }

    /**
     * Conta serviços ativos dentro do tenant atual.
     *
     * @return int Número de serviços ativos
     */
    public function countActive(): int
    {
        return $this->countByTenant( [ 'status' => 'active' ] );
    }

    /**
     * Conta serviços por categoria dentro do tenant atual.
     *
     * @param int $categoryId ID da categoria
     * @return int Número de serviços na categoria
     */
    public function countByCategory( int $categoryId ): int
    {
        return $this->countByTenant( [ 'category_id' => $categoryId ] );
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

    /**
     * Busca serviços com filtros avançados, paginação e eager loading.
     *
     * @param array<string, mixed> $filters Filtros (status, category_id, date_from, date_to, search)
     * @param array<string, string>|null $orderBy Ordenação
     * @param int|null $limit Limite de registros
     * @return Collection<Service> Coleção de serviços filtrados
     */
    public function getFiltered( array $filters = [], ?array $orderBy = null, ?int $limit = null ): Collection
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if ( !empty( $filters[ 'status' ] ) ) {
            $query->where( 'status', $filters[ 'status' ] );
        }

        if ( !empty( $filters[ 'category_id' ] ) ) {
            $query->where( 'category_id', $filters[ 'category_id' ] );
        }

        if ( !empty( $filters[ 'date_from' ] ) ) {
            $query->whereDate( 'created_at', '>=', $filters[ 'date_from' ] );
        }

        if ( !empty( $filters[ 'date_to' ] ) ) {
            $query->whereDate( 'created_at', '<=', $filters[ 'date_to' ] );
        }

        if ( !empty( $filters[ 'search' ] ) ) {
            $query->where( function ( $q ) use ( $filters ) {
                $q->where( 'code', 'like', '%' . $filters[ 'search' ] . '%' )
                    ->orWhere( 'description', 'like', '%' . $filters[ 'search' ] . '%' );
            } );
        }

        // Eager loading padrão
        $query->with( [ 'category', 'budget.customer', 'serviceStatus' ] );

        // Ordenação
        if ( $orderBy ) {
            foreach ( $orderBy as $field => $direction ) {
                $query->orderBy( $field, $direction );
            }
        } else {
            $query->orderBy( 'created_at', 'desc' );
        }

        // Limite
        if ( $limit ) {
            $query->limit( $limit );
        }

        return $query->get();
    }

    /**
     * Busca um serviço por código com eager loading opcional.
     *
     * @param string $code Código do serviço
     * @param array<string> $with Relacionamentos para eager loading
     * @return Service|null Serviço encontrado ou null
     */
    public function findByCode( string $code, array $with = [] ): ?Service
    {
        $query = $this->model->where( 'code', $code );

        if ( !empty( $with ) ) {
            $query->with( $with );
        }

        return $query->first();
    }

    /**
     * Busca um serviço por código dentro do tenant atual.
     *
     * @param string $code Código do serviço
     * @param array<string> $with Relacionamentos para eager loading
     * @return Service|null Serviço encontrado ou null
     */
    public function findByCodeWithTenant( string $code, array $with = [] ): ?Service
    {
        return $this->findByCode( $code, $with );
    }

}
