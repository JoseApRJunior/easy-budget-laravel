<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BudgetPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any budgets.
     */
    public function viewAny(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the budget.
     */
    public function view(User $user, Budget $budget): bool
    {
        if ($budget->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can create budgets.
     */
    public function create(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the budget.
     */
    public function update(User $user, Budget $budget): bool
    {
        if ($budget->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the budget.
     */
    public function delete(User $user, Budget $budget): bool
    {
        if ($budget->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the budget.
     */
    public function restore(User $user, Budget $budget): bool
    {
        if ($budget->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the budget.
     */
    public function forceDelete(User $user, Budget $budget): bool
    {
        if ($budget->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isAdmin();
    }
}
