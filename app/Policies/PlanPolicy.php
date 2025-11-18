<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any plans.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the plan.
     */
    public function view(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create plans.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the plan.
     */
    public function update(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the plan.
     */
    public function delete(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the plan.
     */
    public function restore(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the plan.
     */
    public function forceDelete(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can toggle plan status.
     */
    public function toggleStatus(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can duplicate the plan.
     */
    public function duplicate(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view plan subscribers.
     */
    public function viewSubscribers(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view plan history.
     */
    public function viewHistory(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view plan analytics.
     */
    public function viewAnalytics(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }
}