<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório para gerenciamento de produtos com arquitetura padronizada ("Gold Standard").
 *
 * Segue o padrão estabelecido no CategoryRepository, utilizando métodos auxiliares
 * seguros para aplicação de filtros e ordenação, evitando duplicidade e erros de query.
 */
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
     * Busca produto por SKU.
     */
    public function findBySku( string $sku, array $with = [] ): ?Model
    {
        return $this->model->newQuery()
            ->where( 'sku', $sku )
            ->when( !empty( $with ), fn( $q ) => $q->with( $with ) )
            ->first();
    }

    /**
     * Verifica se o produto pode ser desativado ou deletado.
     * (Ex: não está em uso em itens de serviço ativos, etc).
     */
    public function canBeDeactivatedOrDeleted( int $productId ): bool
    {
        // Verifica se o produto está associado a algum service_item
        // A lógica de negócio pode ser expandida aqui.
        return !$this->model->where( 'id', $productId )->has( 'serviceItems' )->exists();
    }

    /**
     * Conta produtos ativos por tenant.
     */
    public function countActiveByTenant(): int
    {
        return $this->countByTenant( [ 'active' => true ] );
    }

    /**
     * Obtém produtos recentes por tenant.
     */
    public function getRecentByTenant( int $limit = 5 ): Collection
    {
        return $this->getAllByTenant( [], [ 'created_at' => 'desc' ], $limit );
    }

    /**
     * Obtém produtos com estoque baixo por tenant.
     */
    public function getLowStockByTenant(): Collection
    {
        // Join manual para acessar inventário.
        // Idealmente isso seria via relacionamento, mas para performance ok.
        return $this->model->newQuery()
            ->join( 'product_inventory', 'products.id', '=', 'product_inventory.product_id' )
            ->where( 'products.active', true )
            ->whereColumn( 'product_inventory.quantity', '<=', 'product_inventory.min_quantity' )
            ->select( 'products.*', 'product_inventory.quantity', 'product_inventory.min_quantity' )
            ->get();
    }

    /**
     * {@inheritdoc}
     *
     * Implementação específica para produtos com filtros padronizados.
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = [ 'category', 'inventory' ],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        // Remove duplicatas de with
        $with = array_unique( $with );

        return $this->model->newQuery()
            ->with( $with )
            ->tap( fn( $q ) => $this->applyAllProductFilters( $q, $filters ) )
            ->when( !$orderBy, fn( $q ) => $q->orderBy( 'name' ) )
            ->when( $orderBy, fn( $q ) => $this->applyOrderBy( $q, $orderBy ) )
            ->paginate( $this->getEffectivePerPage( $filters, $perPage ) );
    }

    /**
     * Aplica todos os filtros de produto de forma segura.
     *
     * Substitui o uso genérico de applyFilters para evitar problemas com strings vazias
     * e garantir que cada filtro seja aplicado corretamente.
     */
    protected function applyAllProductFilters( Builder $query, array $filters ): void
    {
        // Filtros padrão da Trait (Search, Boolean, SoftDelete)
        $this->applySearchFilter( $query, $filters, [ 'name', 'sku', 'description' ] );
        $this->applyBooleanFilter( $query, $filters, 'active', 'active' );
        $this->applySoftDeleteFilter( $query, $filters );

        // Filtro de Categoria
        $query->when( !empty( $filters[ 'category_id' ] ), function ( $q ) use ( $filters ) {
            $q->where( 'category_id', $filters[ 'category_id' ] );
        } );

        // Filtros de Preço
        $query->when( !empty( $filters[ 'min_price' ] ), function ( $q ) use ( $filters ) {
            $q->where( 'price', '>=', $filters[ 'min_price' ] );
        } );

        $query->when( !empty( $filters[ 'max_price' ] ), function ( $q ) use ( $filters ) {
            $q->where( 'price', '<=', $filters[ 'max_price' ] );
        } );
    }

}
