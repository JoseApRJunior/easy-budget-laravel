<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BudgetStatusChanged;
use App\Mail\BudgetNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Listener para envio de notificações por email quando o status do orçamento muda.
 */
class SendBudgetNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(BudgetStatusChanged $event): void
    {
        try {
            $budget = $event->budget;
            $customer = $budget->customer;

            // Verificar se o cliente tem email
            if (! $customer->contact || ! $customer->contact->email_personal) {
                Log::info('Cliente sem email para notificação', [
                    'budget_id' => $budget->id,
                    'customer_id' => $customer->id,
                ]);

                return;
            }

            // Determinar tipo de notificação baseado no novo status
            $notificationType = match ($event->newStatus) {
                'approved' => 'approved',
                'rejected' => 'rejected',
                'sent' => 'sent',
                'expired' => 'expired',
                default => 'updated'
            };

            // Enviar email
            Mail::to($customer->contact->email_personal)
                ->send(new BudgetNotificationMail(
                    budget: $budget,
                    customer: $customer,
                    notificationType: $notificationType,
                    tenant: $budget->tenant,
                    customMessage: $event->comment
                ));

            Log::info('Notificação de orçamento enviada', [
                'budget_id' => $budget->id,
                'customer_email' => $customer->contact->email_personal,
                'notification_type' => $notificationType,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação de orçamento', [
                'budget_id' => $event->budget->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
