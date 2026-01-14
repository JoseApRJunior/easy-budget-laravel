<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryMovementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any movements.
     */
    public function viewAny(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the movement.
     */
    public function view(User $user, InventoryMovement $movement): bool
    {
        if ($movement->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can create movements.
     */
    public function create(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }
}
