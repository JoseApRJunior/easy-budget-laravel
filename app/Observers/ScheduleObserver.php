<?php

declare(strict_types=1);

namespace App\Observers;

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
        if ($schedule->suppressStatusNotification) {
            return;
        }

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

            // Sincronizar status do serviço se o agendamento for confirmado
            if ($newStatus === \App\Enums\ScheduleStatus::CONFIRMED && $schedule->service) {
                Log::info('Schedule confirmed, updating service status to SCHEDULED', [
                    'schedule_id' => $schedule->id,
                    'service_id' => $schedule->service->id
                ]);

                // Evitar loop infinito suprimindo a notificação do serviço se necessário
                // O ServiceObserver já lida com notificações do serviço
                $schedule->service->update(['status' => \App\Enums\ServiceStatus::SCHEDULED->value]);
            }

            $oldStatusValue = $oldStatus instanceof \UnitEnum ? $oldStatus->value : (string) $oldStatus;

            event(new StatusUpdated(
                $schedule,
                $oldStatusValue,
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
        if ($schedule->suppressStatusNotification) {
            return;
        }

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
