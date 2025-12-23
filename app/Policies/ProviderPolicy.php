<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Provider;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProviderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any providers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage-providers');
    }

    /**
     * Determine whether the user can view the provider.
     */
    public function view(User $user, Provider $provider): bool
    {
        return $user->hasPermission('manage-providers');
    }

    /**
     * Determine whether the user can create providers.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage-providers');
    }

    /**
     * Determine whether the user can update the provider.
     */
    public function update(User $user, Provider $provider): bool
    {
        return $user->hasPermission('manage-providers');
    }

    /**
     * Determine whether the user can delete the provider.
     */
    public function delete(User $user, Provider $provider): bool
    {
        return $user->hasPermission('manage-providers');
    }

    /**
     * Determine whether the user can restore the provider.
     */
    public function restore(User $user, Provider $provider): bool
    {
        return $user->hasPermission('manage-providers');
    }

    /**
     * Determine whether the user can permanently delete the provider.
     */
    public function forceDelete(User $user, Provider $provider): bool
    {
        return $user->hasPermission('manage-providers');
    }

    /**
     * Determine whether the user can export providers.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('manage-providers');
    }

    /**
     * Determine whether the user can view provider statistics.
     */
    public function viewStats(User $user): bool
    {
        return $user->hasPermission('manage-providers');
    }

    /**
     * Determine whether the user can manage provider plans.
     */
    public function managePlans(User $user, Provider $provider): bool
    {
        return $user->hasPermission('manage-providers') && $user->hasPermission('manage-plans');
    }

    /**
     * Determine whether the user can manage provider billing.
     */
    public function manageBilling(User $user, Provider $provider): bool
    {
        return $user->hasPermission('manage-providers') && $user->hasPermission('manage-billing');
    }

    /**
     * Determine whether the user can manage provider subscriptions.
     */
    public function manageSubscriptions(User $user, Provider $provider): bool
    {
        return $user->hasPermission('manage-providers') && $user->hasPermission('manage-subscriptions');
    }

    /**
     * Determine whether the user can suspend/unsuspend providers.
     */
    public function suspend(User $user, Provider $provider): bool
    {
        return $user->hasPermission('manage-providers') && $user->hasPermission('suspend-providers');
    }

    /**
     * Determine whether the user can view provider financial data.
     */
    public function viewFinancialData(User $user, Provider $provider): bool
    {
        return $user->hasPermission('manage-providers') && $user->hasPermission('view-financial-data');
    }

    /**
     * Determine whether the user can manage provider customers.
     */
    public function manageCustomers(User $user, Provider $provider): bool
    {
        return $user->hasPermission('manage-providers') && $user->hasPermission('manage-customers');
    }

    /**
     * Determine whether the user can assign providers to tenants.
     */
    public function assignToTenant(User $user, Provider $provider): bool
    {
        return $user->hasPermission('manage-providers') && $user->hasPermission('manage-tenants');
    }
}