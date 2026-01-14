<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any services.
     */
    public function viewAny(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the service.
     */
    public function view(User $user, Service $service): bool
    {
        if ($service->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can create services.
     */
    public function create(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the service.
     */
    public function update(User $user, Service $service): bool
    {
        if ($service->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the service.
     */
    public function delete(User $user, Service $service): bool
    {
        if ($service->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the service.
     */
    public function restore(User $user, Service $service): bool
    {
        if ($service->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isProvider() || $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the service.
     */
    public function forceDelete(User $user, Service $service): bool
    {
        if ($service->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->isAdmin();
    }
}
