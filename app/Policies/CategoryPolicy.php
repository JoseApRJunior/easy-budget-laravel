<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Category;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage-categories');
    }

    /**
     * Determine whether the user can view the category.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->hasPermission('manage-categories');
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage-categories');
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->hasPermission('manage-categories');
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermission('manage-categories');
    }

    /**
     * Determine whether the user can restore the category.
     */
    public function restore(User $user, Category $category): bool
    {
        return $user->hasPermission('manage-categories');
    }

    /**
     * Determine whether the user can permanently delete the category.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        return $user->hasPermission('manage-categories');
    }

    /**
     * Determine whether the user can export categories.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('manage-categories');
    }

    /**
     * Determine whether the user can view category statistics.
     */
    public function viewStats(User $user): bool
    {
        return $user->hasPermission('manage-categories');
    }

    /**
     * Determine whether the user can manage category activities.
     */
    public function manageActivities(User $user, Category $category): bool
    {
        return $user->hasPermission('manage-categories') && $user->hasPermission('manage-activities');
    }

    /**
     * Determine whether the user can assign categories to tenants.
     */
    public function assignToTenant(User $user, Category $category): bool
    {
        return $user->hasPermission('manage-categories') && $user->hasPermission('manage-tenants');
    }
}
