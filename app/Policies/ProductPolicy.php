<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage-inventory') || $user->hasPermission('view-inventory');
    }

    /**
     * Determine whether the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        // Verificar se o produto pertence ao tenant do usuário
        if ($product->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasPermission('manage-inventory') || $user->hasPermission('view-inventory');
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage-inventory');
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        // Verificar se o produto pertence ao tenant do usuário
        if ($product->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasPermission('manage-inventory');
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        // Verificar se o produto pertence ao tenant do usuário
        if ($product->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasPermission('manage-inventory');
    }

    /**
     * Determine whether the user can adjust inventory.
     */
    public function adjustInventory(User $user, Product $product): bool
    {
        // Verificar se o produto pertence ao tenant do usuário
        if ($product->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasPermission('manage-inventory');
    }

    /**
     * Determine whether the user can view inventory reports.
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('manage-inventory') || $user->hasPermission('view-inventory-reports');
    }

    /**
     * Determine whether the user can view inventory movements.
     */
    public function viewMovements(User $user): bool
    {
        return $user->hasPermission('manage-inventory') || $user->hasPermission('view-inventory');
    }

    /**
     * Determine whether the user can manage inventory alerts.
     */
    public function manageAlerts(User $user): bool
    {
        return $user->hasPermission('manage-inventory') || $user->hasPermission('view-inventory-alerts');
    }
}