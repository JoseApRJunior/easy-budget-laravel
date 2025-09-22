<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;

class CategoryRepository extends AbstractRepository implements RepositoryInterface
{
    protected string $modelClass = Category::class;

    /**
     * Verifica se categoria existe por ID e tenant.
     */
    public function existsByIdAndTenantId( int $id, int $tenantId ): bool
    {
        return $this->model::where( 'id', $id )
            ->where( 'tenant_id', $tenantId )
            ->exists();
    }

    /**
     * Lista categorias ativas por tenant.
     */
    public function listActiveByTenantId( int $tenantId, ?array $orderBy = null ): array
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->where( 'status', 'active' );
        if ( $orderBy ) {
            foreach ( $orderBy as $column => $direction ) {
                $query->orderBy( $column, $direction );
            }
        }
        return $query->get()->all();
    }

}
