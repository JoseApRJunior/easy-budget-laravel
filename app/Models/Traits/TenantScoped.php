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
     * Static flag to prevent recursion when checking auth during tenant resolution.
     */
    private static bool $resolvingTenant = false;

    /**
     * Apply the scope to the given query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip applying scope if we're already resolving tenant to prevent infinite loop
        if (self::$resolvingTenant) {
            return;
        }

        $tenantId = $this->getCurrentTenantId($model);

        if ($tenantId !== null) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }

    /**
     * Get the current tenant ID from auth context or request.
     */
    public function getCurrentTenantId(Model $model): ?int
    {
        // Verificação especial para contexto de testes: usa config('tenant.testing_id')
        $testingTenantId = config('tenant.testing_id');
        if ($testingTenantId !== null) {
            return (int) $testingTenantId;
        }

        // Set recursion protection flag before checking auth
        self::$resolvingTenant = true;

        try {
            // Prioritize authenticated user tenant
            if (Auth::check()) {
                $user = Auth::user();
                if (method_exists($user, 'tenant') && $user->tenant) {
                    return $user->tenant->id;
                }
            }

            // Fallback to request context if available
            if (request()->has('tenant_id')) {
                return (int) request()->get('tenant_id');
            }

            // Return null if no tenant resolved to avoid applying scope
            return null;
        } finally {
            // Always reset the recursion protection flag
            self::$resolvingTenant = false;
        }

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
     */
    public static function bootTenantScoped(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Remove the tenant scope from the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     */
    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Get the current tenant ID.
     */
    public static function getCurrentTenantId(): ?int
    {
        $scope = new TenantScope;
        $dummyModel = new class extends Model {};
        $dummyModel->setRawAttributes([]);

        return $scope->getCurrentTenantId($dummyModel);
    }

    /**
     * Set the testing tenant ID for test environments.
     * This method allows setting a fixed tenant_id for testing purposes.
     */
    public static function setTestingTenantId(?int $tenantId): void
    {
        config(['tenant.testing_id' => $tenantId]);
    }

    /**
     * Check if the model is scoped to a specific tenant.
     */
    public function isScopedToTenant(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }

    /**
     * Set the tenant ID explicitly (for seeding or admin operations).
     *
     * @return $this
     */
    public function setTenantId(int $tenantId): static
    {
        $this->tenant_id = $tenantId;

        return $this;
    }

    /**
     * Get all records without tenant scoping (admin only).
     */
    public function scopeAllTenants(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
