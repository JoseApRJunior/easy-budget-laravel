<?php

namespace App\Repositories;

use App\Models\ProductInventory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Repositório para gerenciamento de inventário de produtos
 */
class ProductInventoryRepository extends AbstractTenantRepository
{
    /**
     * @var ProductInventory
     */
    protected $model;

    /**
     * Create a new repository instance.
     */
    public function __construct( ProductInventory $model )
    {
        $this->model = $model;
    }

    /**
     * Retorna inventário paginado com filtros
     */
    public function getPaginated( int $perPage = 15, array $filters = [] ): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with( ['product.category'] )
            ->when( $filters['search'] ?? null, function ( Builder $query, $search ) {
                $query->whereHas( 'product', function ( Builder $q ) use ( $search ) {
                    $q->where( 'name', 'like', "%{$search}%" )
                      ->orWhere( 'sku', 'like', "%{$search}%" );
                } );
            } )
            ->when( $filters['status'] ?? null, function ( Builder $query, $status ) {
                match ( $status ) {
                    'low'    => $query->lowStock(),
                    'high'   => $query->highStock(),
                    'ideal'  => $query->whereRaw( 'quantity > min_quantity AND (max_quantity IS NULL OR quantity < max_quantity)' ),
                    default  => null,
                };
            } )
            ->when( $filters['low_stock'] ?? null, function ( Builder $query ) {
                $query->lowStock();
            } )
            ->when( $filters['category_id'] ?? null, function ( Builder $query, $categoryId ) {
                $query->whereHas( 'product', function ( Builder $q ) use ( $categoryId ) {
                    $q->where( 'category_id', $categoryId );
                } );
            } )
            ->orderBy( 'updated_at', 'desc' )
            ->paginate( $perPage );
    }

    /**
     * Busca inventário por produto
     */
    public function findByProduct( int $productId ): ?ProductInventory
    {
        return $this->model->newQuery()
            ->with( ['product', 'movements'] )
            ->where( 'product_id', $productId )
            ->first();
    }

    /**
     * Cria ou atualiza inventário para um produto
     */
    public function updateOrCreate( int $productId, array $data ): ProductInventory
    {
        return $this->model->updateOrCreate(
            [
                'tenant_id'  => tenant()->id,
                'product_id' => $productId,
            ],
            $data
        );
    }

    /**
     * Atualiza quantidade do inventário
     */
    public function updateQuantity( int $productId, int $newQuantity ): ProductInventory
    {
        $inventory = $this->findByProduct( $productId );

        if ( !$inventory ) {
            $inventory = $this->model->create( [
                'tenant_id'    => tenant()->id,
                'product_id'   => $productId,
                'quantity'     => $newQuantity,
                'min_quantity' => 0,
            ] );
        } else {
            $inventory->update( ['quantity' => $newQuantity] );
        }

        return $inventory;
    }

    /**
     * Atualiza quantidade mínima
     */
    public function updateMinQuantity( int $productId, int $minQuantity ): ProductInventory
    {
        return $this->updateOrCreate( $productId, ['min_quantity' => $minQuantity] );
    }

    /**
     * Atualiza quantidade máxima
     */
    public function updateMaxQuantity( int $productId, ?int $maxQuantity ): ProductInventory
    {
        return $this->updateOrCreate( $productId, ['max_quantity' => $maxQuantity] );
    }

    /**
     * Retorna produtos com estoque baixo
     */
    public function getLowStockItems(): Collection
    {
        return $this->model->newQuery()
            ->with( ['product'] )
            ->lowStock()
            ->get();
    }

    /**
     * Retorna contador de produtos com estoque baixo
     */
    public function getLowStockCount(): int
    {
        return $this->model->newQuery()
            ->lowStock()
            ->count();
    }

    /**
     * Busca inventário por termo
     */
    public function searchInventory( ?string $search, int $limit = 10 ): Collection
    {
        return $this->model->newQuery()
            ->with( ['product'] )
            ->when( $search, function ( Builder $query, $search ) {
                $query->whereHas( 'product', function ( Builder $q ) use ( $search ) {
                    $q->where( 'name', 'like', "%{$search}%" )
                      ->orWhere( 'sku', 'like', "%{$search}%" );
                } );
            } )
            ->limit( $limit )
            ->get();
    }

    /**
     * Retorna estatísticas do inventário
     */
    public function getStatistics(): array
    {
        $query = $this->model->newQuery();

        return [
            'total_items'        => $query->count(),
            'low_stock_items'  => $query->clone()->lowStock()->count(),
            'high_stock_items' => $query->clone()->highStock()->count(),
            'ideal_stock_items'=> $query->clone()
                ->whereRaw( 'quantity > min_quantity' )
                ->whereRaw( '(max_quantity IS NULL OR quantity < max_quantity)' )
                ->count(),
            'total_value'      => 0, // Será calculado no service
        ];
    }
}