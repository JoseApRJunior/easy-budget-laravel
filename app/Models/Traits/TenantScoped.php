<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to the given query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply( Builder $builder, Model $model ): void
    {
        $tenantId = $this->getCurrentTenantId( $model );

        if ( $tenantId !== null ) {
            $builder->where( 'tenant_id', $tenantId );
        }
    }

    /**
     * Get the current tenant ID from auth context or request.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return int|null
     */
    public function getCurrentTenantId( Model $model ): ?int
    {
        // Verificação especial para contexto de testes: usa config('tenant.testing_id')
        $testingTenantId = config( 'tenant.testing_id' );
        if ( $testingTenantId !== null ) {
            return (int) $testingTenantId;
        }

        // Prioritize authenticated user tenant
        if ( Auth::check() ) {
            $user = Auth::user();
            if ( method_exists( $user, 'tenant' ) && $user->tenant ) {
                return $user->tenant->id;
            }
        }

        // Fallback to request context if available
        if ( request()->has( 'tenant_id' ) ) {
            return (int) request()->get( 'tenant_id' );
        }

        // Return null if no tenant resolved to avoid applying scope
        return null;

        // Note: In production, implement proper tenant resolution middleware
    }

}

/**
 * Trait TenantScoped
 *
 * Provides automatic tenant scoping for Eloquent models.
 * Automatically filters queries by the current tenant_id.
 * Includes methods to bypass scoping when needed (e.g., admin operations).
 *
 * Para uso em testes: Configure config(['tenant.testing_id' => $id]) para definir um tenant_id fixo
 * durante a execução de testes. Isso substitui a lógica normal de resolução de tenant (Auth/Request)
 * e aplica o scoping baseado no ID fornecido. Útil para isolar testes por tenant sem mocks complexos.
 */
trait TenantScoped
{

    /**
     * Boot the trait and apply the tenant scope.
     *
     * @return void
     */
    public static function bootTenantScoped(): void
    {
        static::addGlobalScope( new TenantScope() );
    }

    /**
     * Remove the tenant scope from the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutTenant( Builder $query ): Builder
    {
        return $query->withoutGlobalScope( TenantScope::class);
    }

    /**
     * Get the current tenant ID.
     *
     * @return int|null
     */
    public static function getCurrentTenantId(): ?int
    {
        $scope      = new TenantScope();
        $dummyModel = new class extends Model
        {};
        $dummyModel->setRawAttributes( [] );
        return $scope->getCurrentTenantId( $dummyModel );
    }

    /**
     * Check if the model is scoped to a specific tenant.
     *
     * @param  int  $tenantId
     * @return bool
     */
    public function isScopedToTenant( int $tenantId ): bool
    {
        return $this->tenant_id === $tenantId;
    }

    /**
     * Set the tenant ID explicitly (for seeding or admin operations).
     *
     * @param  int  $tenantId
     * @return $this
     */
    public function setTenantId( int $tenantId ): static
    {
        $this->tenant_id = $tenantId;
        return $this;
    }

    /**
     * Get all records without tenant scoping (admin only).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAllTenants( Builder $query ): Builder
    {
        return $query->withoutGlobalScope( TenantScope::class);
    }

}
