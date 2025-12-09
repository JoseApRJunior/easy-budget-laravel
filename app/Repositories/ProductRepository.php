<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProductRepository extends AbstractTenantRepository
{
    public function __construct( Product $model )
    {
        $this->model = $model;
    }

    protected function makeModel(): Model
    {
        return new Product();
    }

    public function getPaginated( array $filters = [], int $perPage = 15, array $with = [] ): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if ( !empty( $with ) ) {
            $query->with( $with );
        }

        if ( !empty( $filters[ 'search' ] ) ) {
            $query->where( function ( $q ) use ( $filters ) {
                $q->where( 'name', 'like', '%' . $filters[ 'search' ] . '%' )
                    ->orWhere( 'sku', 'like', '%' . $filters[ 'search' ] . '%' )
                    ->orWhere( 'description', 'like', '%' . $filters[ 'search' ] . '%' );
            } );
        }

        if ( isset( $filters[ 'active' ] ) && $filters[ 'active' ] !== '' ) {
            $query->where( 'active', (bool) $filters[ 'active' ] );
        }

        if ( !empty( $filters[ 'category_id' ] ) ) {
            $query->where( 'category_id', $filters[ 'category_id' ] );
        }

        if ( !empty( $filters[ 'min_price' ] ) ) {
            $query->where( 'price', '>=', $filters[ 'min_price' ] );
        }

        if ( !empty( $filters[ 'max_price' ] ) ) {
            $query->where( 'price', '<=', $filters[ 'max_price' ] );
        }

        // Usar per_page do filtro se fornecido
        $itemsPerPage = !empty( $filters[ 'per_page' ] ) ? (int) $filters[ 'per_page' ] : $perPage;

        return $query->orderBy( 'name', 'asc' )->paginate( $itemsPerPage );
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
