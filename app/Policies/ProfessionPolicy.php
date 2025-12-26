<?php

namespace App\Policies;

use App\Models\Profession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfessionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any professions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage-professions');
    }

    /**
     * Determine whether the user can view the profession.
     */
    public function view(User $user, Profession $profession): bool
    {
        return $user->hasPermission('manage-professions');
    }

    /**
     * Determine whether the user can create professions.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage-professions');
    }

    /**
     * Determine whether the user can update the profession.
     */
    public function update(User $user, Profession $profession): bool
    {
        return $user->hasPermission('manage-professions');
    }

    /**
     * Determine whether the user can delete the profession.
     */
    public function delete(User $user, Profession $profession): bool
    {
        return $user->hasPermission('manage-professions');
    }

    /**
     * Determine whether the user can restore the profession.
     */
    public function restore(User $user, Profession $profession): bool
    {
        return $user->hasPermission('manage-professions');
    }

    /**
     * Determine whether the user can permanently delete the profession.
     */
    public function forceDelete(User $user, Profession $profession): bool
    {
        return $user->hasPermission('manage-professions');
    }

    /**
     * Determine whether the user can export professions.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('manage-professions');
    }

    /**
     * Determine whether the user can view profession statistics.
     */
    public function viewStats(User $user): bool
    {
        return $user->hasPermission('manage-professions');
    }

    /**
     * Determine whether the user can manage profession salary data.
     */
    public function manageSalary(User $user, Profession $profession): bool
    {
        return $user->hasPermission('manage-professions') && $user->hasPermission('manage-salary-data');
    }

    /**
     * Determine whether the user can manage profession job market data.
     */
    public function manageJobMarket(User $user, Profession $profession): bool
    {
        return $user->hasPermission('manage-professions') && $user->hasPermission('manage-job-market');
    }

    /**
     * Determine whether the user can assign professions to users.
     */
    public function assignToUser(User $user, Profession $profession): bool
    {
        return $user->hasPermission('manage-professions') && $user->hasPermission('manage-users');
    }

    /**
     * Determine whether the user can assign professions to providers.
     */
    public function assignToProvider(User $user, Profession $profession): bool
    {
        return $user->hasPermission('manage-professions') && $user->hasPermission('manage-providers');
    }

    /**
     * Determine whether the user can manage profession education requirements.
     */
    public function manageEducation(User $user, Profession $profession): bool
    {
        return $user->hasPermission('manage-professions') && $user->hasPermission('manage-education');
    }

    /**
     * Determine whether the user can assign professions to tenants.
     */
    public function assignToTenant(User $user, Profession $profession): bool
    {
        return $user->hasPermission('manage-professions') && $user->hasPermission('manage-tenants');
    }
}
