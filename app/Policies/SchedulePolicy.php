<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchedulePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any schedules.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'provider']);
    }

    /**
     * Determine whether the user can view the schedule.
     */
    public function view(User $user, Schedule $schedule): bool
    {
        return $user->tenant_id === $schedule->tenant_id && 
               ($user->hasRole(['admin', 'provider']) || 
                $user->id === $schedule->service->customer->user_id);
    }

    /**
     * Determine whether the user can create schedules.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'provider']);
    }

    /**
     * Determine whether the user can update the schedule.
     */
    public function update(User $user, Schedule $schedule): bool
    {
        return $user->tenant_id === $schedule->tenant_id && 
               $user->hasRole(['admin', 'provider']);
    }

    /**
     * Determine whether the user can delete the schedule.
     */
    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->tenant_id === $schedule->tenant_id && 
               $user->hasRole(['admin', 'provider']);
    }
}