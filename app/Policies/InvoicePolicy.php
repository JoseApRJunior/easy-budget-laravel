<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if ($invoice->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        if ($invoice->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        if ($invoice->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the invoice.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        if ($invoice->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the invoice.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        if ($invoice->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isAdmin();
    }
}
