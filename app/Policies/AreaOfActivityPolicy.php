<?php

namespace App\Policies;

use App\Models\AreaOfActivity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AreaOfActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any activities.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage-activities');
    }

    /**
     * Determine whether the user can view the activity.
     */
    public function view(User $user, AreaOfActivity $activity): bool
    {
        return $user->hasPermission('manage-activities');
    }

    /**
     * Determine whether the user can create activities.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage-activities');
    }

    /**
     * Determine whether the user can update the activity.
     */
    public function update(User $user, AreaOfActivity $activity): bool
    {
        return $user->hasPermission('manage-activities');
    }

    /**
     * Determine whether the user can delete the activity.
     */
    public function delete(User $user, AreaOfActivity $activity): bool
    {
        return $user->hasPermission('manage-activities');
    }

    /**
     * Determine whether the user can restore the activity.
     */
    public function restore(User $user, AreaOfActivity $activity): bool
    {
        return $user->hasPermission('manage-activities');
    }

    /**
     * Determine whether the user can permanently delete the activity.
     */
    public function forceDelete(User $user, AreaOfActivity $activity): bool
    {
        return $user->hasPermission('manage-activities');
    }

    /**
     * Determine whether the user can export activities.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('manage-activities');
    }

    /**
     * Determine whether the user can view activity statistics.
     */
    public function viewStats(User $user): bool
    {
        return $user->hasPermission('manage-activities');
    }

    /**
     * Determine whether the user can manage activity pricing.
     */
    public function managePricing(User $user, AreaOfActivity $activity): bool
    {
        return $user->hasPermission('manage-activities') && $user->hasPermission('manage-pricing');
    }

    /**
     * Determine whether the user can assign activities to categories.
     */
    public function assignToCategory(User $user, AreaOfActivity $activity): bool
    {
        return $user->hasPermission('manage-activities') && $user->hasPermission('manage-categories');
    }

    /**
     * Determine whether the user can manage activity products/services.
     */
    public function manageProducts(User $user, AreaOfActivity $activity): bool
    {
        return $user->hasPermission('manage-activities') && $user->hasPermission('manage-products');
    }

    /**
     * Determine whether the user can assign activities to tenants.
     */
    public function assignToTenant(User $user, AreaOfActivity $activity): bool
    {
        return $user->hasPermission('manage-activities') && $user->hasPermission('manage-tenants');
    }
}
