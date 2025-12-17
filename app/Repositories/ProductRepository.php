<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends AbstractTenantRepository
{
    /**
     * {@inheritdoc}
     */
    protected function makeModel(): Model
    {
        return new Product();
    }

    /**
     * {@inheritdoc}
     *
     * Override do método padrão para adicionar filtros específicos de produto.
     * Mantém compatibilidade com assinatura do AbstractTenantRepository.
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        // Eager loading padrão para produtos
        $defaultWith = [ 'category', 'inventory' ];
        $with        = array_unique( array_merge( $defaultWith, $with ) );

        // Adicionar filtros específicos do ProductRepository
        $query = $this->model->newQuery();

        // Eager loading paramétrico
        if ( !empty( $with ) ) {
            $query->with( $with );
        }

        // Filtro por busca (nome, SKU, descrição)
        if ( !empty( $filters[ 'search' ] ) ) {
            $query->where( function ( $q ) use ( $filters ) {
                $q->where( 'name', 'like', '%' . $filters[ 'search' ] . '%' )
                    ->orWhere( 'sku', 'like', '%' . $filters[ 'search' ] . '%' )
                    ->orWhere( 'description', 'like', '%' . $filters[ 'search' ] . '%' );
            } );
        }

        // Filtro por ativo/inativo
        if ( isset( $filters[ 'active' ] ) && $filters[ 'active' ] !== '' ) {
            $query->where( 'active', (bool) $filters[ 'active' ] );
        }

        // Filtro por categoria
        if ( !empty( $filters[ 'category_id' ] ) ) {
            $query->where( 'category_id', $filters[ 'category_id' ] );
        }

        // Filtros de preço
        if ( !empty( $filters[ 'min_price' ] ) ) {
            $query->where( 'price', '>=', $filters[ 'min_price' ] );
        }

        if ( !empty( $filters[ 'max_price' ] ) ) {
            $query->where( 'price', '<=', $filters[ 'max_price' ] );
        }

        // Aplicar filtros padrão do trait
        $this->applyFilters( $query, $filters );
        $this->applySoftDeleteFilter( $query, $filters );

        // Aplicar ordenação (padrão: nome ascendente)
        $defaultOrderBy = $orderBy ?: [ 'name' => 'asc' ];
        $this->applyOrderBy( $query, $defaultOrderBy );

        // Per page dinâmico
        $effectivePerPage = $this->getEffectivePerPage( $filters, $perPage );

        return $query->paginate( $effectivePerPage );
    }

    public function findBySku( string $sku, array $with = [] ): ?Model
    {
        $query = $this->model->where( 'sku', $sku );

        if ( !empty( $with ) ) {
            $query->with( $with );
        }

        return $query->first();
    }

    public function countActive(): int
    {
        return $this->model->where( 'active', true )->count();
    }

    public function canBeDeactivatedOrDeleted( int $productId ): bool
    {
        // Verifica se o produto está associado a algum service_item
        return !$this->model->where( 'id', $productId )->has( 'serviceItems' )->exists();
    }

    /**
     * Conta produtos ativos por tenant
     */
    public function countActiveByTenant(): int
    {
        return $this->countByTenant( [ 'active' => true ] );
    }

    /**
     * Obtém produtos recentes por tenant
     */
    public function getRecentByTenant( int $limit = 5 ): Collection
    {
        return $this->getAllByTenant( [], [ 'created_at' => 'desc' ], $limit );
    }

    /**
     * Obtém produtos com estoque baixo por tenant
     */
    public function getLowStockByTenant(): Collection
    {
        return $this->model->join( 'product_inventory', 'products.id', '=', 'product_inventory.product_id' )
            ->where( 'products.active', true )
            ->whereColumn( 'product_inventory.quantity', '<=', 'product_inventory.min_quantity' )
            ->select( 'products.*', 'product_inventory.quantity', 'product_inventory.min_quantity' )
            ->get();
    }

}
