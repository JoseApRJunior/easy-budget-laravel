<?php

namespace App\Repositories;

use App\Models\ProductInventory;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class InventoryRepository extends AbstractTenantRepository
{
    public function __construct( ProductInventory $model )
    {
        $this->model = $model;
    }

    protected function makeModel(): Model
    {
        return new ProductInventory();
    }

    // ========== MÉTODOS ORIGINAIS ==========

    public function findByProductId( int $productId ): ?ProductInventory
    {
        return $this->model->where( 'product_id', $productId )->first();
    }

    public function createForProduct( int $productId, array $data ): ProductInventory
    {
        return $this->model->create( array_merge( $data, [ 'product_id' => $productId ] ) );
    }

    public function updateStock( int $productId, int $quantity ): bool
    {
        return $this->model->where( 'product_id', $productId )->update( [ 'quantity' => $quantity ] );
    }

    // ========== MÉTODOS ADICIONADOS DE ProductInventoryRepository ==========

    public function getPaginated( int $tenantId, int $perPage = 15, array $filters = [] )
    {
        $query = $this->model->where( 'tenant_id', $tenantId )->with( 'product' );

        if ( !empty( $filters[ 'search' ] ) ) {
            $query->whereHas( 'product', function ( $q ) use ( $filters ) {
                $q->where( 'name', 'like', "%{$filters[ 'search' ]}%" )
                    ->orWhere( 'sku', 'like', "%{$filters[ 'search' ]}%" );
            } );
        }

        if ( isset( $filters[ 'low_stock' ] ) && $filters[ 'low_stock' ] ) {
            $query->whereColumn( 'quantity', '<=', 'min_quantity' );
        }

        if ( isset( $filters[ 'high_stock' ] ) && $filters[ 'high_stock' ] ) {
            $query->whereColumn( 'quantity', '>=', 'max_quantity' );
        }

        return $query->paginate( $perPage );
    }

    public function findByProduct( int $productId, int $tenantId ): ?ProductInventory
    {
        return $this->model
            ->where( 'product_id', $productId )
            ->where( 'tenant_id', $tenantId )
            ->first();
    }

    public function updateOrCreate( int $productId, int $tenantId, array $data ): ProductInventory
    {
        return $this->model->updateOrCreate(
            [ 'product_id' => $productId, 'tenant_id' => $tenantId ],
            $data,
        );
    }

    public function updateQuantity( int $productId, int $tenantId, int $quantity ): bool
    {
        return $this->model
            ->where( 'product_id', $productId )
            ->where( 'tenant_id', $tenantId )
            ->update( [ 'quantity' => $quantity ] );
    }

    public function updateMinQuantity( int $productId, int $tenantId, int $minQuantity ): bool
    {
        return $this->model
            ->where( 'product_id', $productId )
            ->where( 'tenant_id', $tenantId )
            ->update( [ 'min_quantity' => $minQuantity ] );
    }

    public function updateMaxQuantity( int $productId, int $tenantId, int $maxQuantity ): bool
    {
        return $this->model
            ->where( 'product_id', $productId )
            ->where( 'tenant_id', $tenantId )
            ->update( [ 'max_quantity' => $maxQuantity ] );
    }

    public function getLowStockItems( int $tenantId, int $limit = 10 ): Collection
    {
        return $this->model
            ->where( 'tenant_id', $tenantId )
            ->whereColumn( 'quantity', '<=', 'min_quantity' )
            ->with( 'product' )
            ->limit( $limit )
            ->get();
    }

    public function getLowStockCount( int $tenantId ): int
    {
        return $this->model
            ->where( 'tenant_id', $tenantId )
            ->whereColumn( 'quantity', '<=', 'min_quantity' )
            ->count();
    }

    public function searchInventory( int $tenantId, string $search ): Collection
    {
        return $this->model
            ->where( 'tenant_id', $tenantId )
            ->whereHas( 'product', function ( $q ) use ( $search ) {
                $q->where( 'name', 'like', "%{$search}%" )
                    ->orWhere( 'sku', 'like', "%{$search}%" );
            } )
            ->with( 'product' )
            ->get();
    }

    public function getStatistics( int $tenantId ): array
    {
        $total      = $this->model->where( 'tenant_id', $tenantId )->count();
        $lowStock   = $this->getLowStockCount( $tenantId );
        $totalValue = $this->model
            ->where( 'tenant_id', $tenantId )
            ->join( 'products', 'product_inventory.product_id', '=', 'products.id' )
            ->selectRaw( 'SUM(product_inventory.quantity * products.price) as total' )
            ->value( 'total' ) ?? 0;

        return [
            'total_items'           => $total,
            'low_stock_items'       => $lowStock,
            'total_inventory_value' => $totalValue,
        ];
    }

}
