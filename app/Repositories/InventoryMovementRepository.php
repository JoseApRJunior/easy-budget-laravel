<?php

namespace App\Repositories;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Repositório para gerenciamento de movimentações de inventário
 */
class InventoryMovementRepository extends AbstractTenantRepository
{
        /**
     * Create a new repository instance.
     */
    public function __construct( InventoryMovement $model )
    {
        $this->model = $model;
    }

    /**
     * Create a new model instance.
     */
    protected function makeModel(): Model
    {
        return new InventoryMovement();
    }

    /**
     * Retorna movimentações por produto
     */
    public function getByProduct( int $productId, int $perPage = 20 ): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with( ['product'] )
            ->where( 'product_id', $productId )
            ->orderBy( 'created_at', 'desc' )
            ->paginate( $perPage );
    }

    /**
     * Retorna todas as movimentações com filtros
     */
    public function getPaginated( int $perPage = 20, array $filters = [] ): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with( ['product'] )
            ->when( $filters['product_id'] ?? null, function ( $query, $productId ) {
                $query->where( 'product_id', $productId );
            } )
            ->when( $filters['type'] ?? null, function ( $query, $type ) {
                $query->where( 'type', $type );
            } )
            ->when( $filters['start_date'] ?? null, function ( $query, $startDate ) {
                $query->whereDate( 'created_at', '>=', $startDate );
            } )
            ->when( $filters['end_date'] ?? null, function ( $query, $endDate ) {
                $query->whereDate( 'created_at', '<=', $endDate );
            } )
            ->when( $filters['reason'] ?? null, function ( $query, $reason ) {
                $query->where( 'reason', 'like', "%{$reason}%" );
            } )
            ->orderBy( 'created_at', 'desc' )
            ->paginate( $perPage );
    }

    /**
     * Cria uma movimentação de inventário
     */
    public function create( array $data ): InventoryMovement
    {
        return $this->model->create( $data );
    }

    /**
     * Retorna estatísticas de movimentações por período
     */
    public function getStatisticsByPeriod( ?string $startDate = null, ?string $endDate = null ): array
    {
        $query = $this->model->newQuery();

        if ( $startDate ) {
            $query->whereDate( 'created_at', '>=', $startDate );
        }

        if ( $endDate ) {
            $query->whereDate( 'created_at', '<=', $endDate );
        }

        $movementsIn = $query->clone()->where( 'type', 'in' );
        $movementsOut = $query->clone()->where( 'type', 'out' );

        return [
            'total_movements' => $query->count(),
            'total_in'        => $movementsIn->count(),
            'total_out'       => $movementsOut->count(),
            'quantity_in'     => $movementsIn->sum( 'quantity' ),
            'quantity_out'    => $movementsOut->sum( 'quantity' ),
            'net_movement'    => $movementsIn->sum( 'quantity' ) - $movementsOut->sum( 'quantity' ),
        ];
    }

    /**
     * Retorna movimentações recentes
     */
    public function getRecentMovements( int $limit = 10 ): Collection
    {
        return $this->model->newQuery()
            ->with( ['product'] )
            ->orderBy( 'created_at', 'desc' )
            ->limit( $limit )
            ->get();
    }

    /**
     * Retorna produtos mais movimentados
     */
    public function getMostMovedProducts( int $limit = 10, ?string $startDate = null, ?string $endDate = null ): Collection
    {
        return $this->model->newQuery()
            ->selectRaw( 'product_id, SUM(quantity) as total_quantity, COUNT(*) as movement_count' )
            ->when( $startDate, function ( $query ) use ( $startDate ) {
                $query->whereDate( 'created_at', '>=', $startDate );
            } )
            ->when( $endDate, function ( $query ) use ( $endDate ) {
                $query->whereDate( 'created_at', '<=', $endDate );
            } )
            ->groupBy( 'product_id' )
            ->orderByDesc( 'total_quantity' )
            ->limit( $limit )
            ->with( ['product'] )
            ->get();
    }

    /**
     * Retorna resumo de movimentações por tipo
     */
    public function getSummaryByType( ?string $startDate = null, ?string $endDate = null ): array
    {
        $query = $this->model->newQuery();

        if ( $startDate ) {
            $query->whereDate( 'created_at', '>=', $startDate );
        }

        if ( $endDate ) {
            $query->whereDate( 'created_at', '<=', $endDate );
        }

        $summary = $query->selectRaw( 'type, COUNT(*) as count, SUM(quantity) as total_quantity' )
                         ->groupBy( 'type' )
                         ->get();

        return [
            'in'  => $summary->where( 'type', 'in' )->first(),
            'out' => $summary->where( 'type', 'out' )->first(),
        ];
    }
}