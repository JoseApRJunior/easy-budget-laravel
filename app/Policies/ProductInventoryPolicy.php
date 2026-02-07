<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductInventory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductInventoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any inventories.
     */
    public function viewAny(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the inventory.
     */
    public function view(User $user, ProductInventory $inventory): bool
    {
        if ($inventory->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the inventory.
     */
    public function update(User $user, ProductInventory $inventory): bool
    {
        if ($inventory->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }
}
