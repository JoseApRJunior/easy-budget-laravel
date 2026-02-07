<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any tenants.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the tenant.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create tenants.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the tenant.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the tenant.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the tenant.
     */
    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the tenant.
     */
    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can suspend the tenant.
     */
    public function suspend(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can activate the tenant.
     */
    public function activate(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can impersonate the tenant.
     */
    public function impersonate(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view tenant analytics.
     */
    public function viewAnalytics(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view tenant billing.
     */
    public function viewBilling(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }
}
