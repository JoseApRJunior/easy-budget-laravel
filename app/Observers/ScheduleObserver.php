<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ScheduleStatus;
use App\Events\StatusUpdated;
use App\Models\Schedule;
use Illuminate\Support\Facades\Log;

class ScheduleObserver
{
    /**
     * Handle the Schedule "updated" event.
     */
    public function updated(Schedule $schedule): void
    {
        Log::info('ScheduleObserver updated method called', [
            'schedule_id' => $schedule->id,
            'status' => $schedule->status->value,
            'is_dirty' => $schedule->isDirty('status'),
            'original_status' => $schedule->getOriginal('status'),
        ]);

        // Disparar evento de notificação se o status mudou
        if ($schedule->isDirty('status')) {
            $oldStatus = $schedule->getOriginal('status');
            $newStatus = $schedule->status;
            
            event(new StatusUpdated(
                $schedule,
                $oldStatus instanceof ScheduleStatus ? $oldStatus->value : (string)$oldStatus,
                $newStatus->value,
                $newStatus->label(),
                $schedule->tenant
            ));
        }
    }

    /**
     * Handle the Schedule "created" event.
     */
    public function created(Schedule $schedule): void
    {
        Log::info('ScheduleObserver created method called', [
            'schedule_id' => $schedule->id,
            'status' => $schedule->status->value,
        ]);

        // Opcional: Notificar criação de novo agendamento
        // Por enquanto, vamos disparar o StatusUpdated para o status inicial
        event(new StatusUpdated(
            $schedule,
            'none',
            $schedule->status->value,
            $schedule->status->label(),
            $schedule->tenant
        ));
    }
}
