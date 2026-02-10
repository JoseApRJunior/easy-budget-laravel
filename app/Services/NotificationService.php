<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\AlertNotificationMail;
use App\Models\MonitoringAlertsHistory;
use App\Models\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Envia notificação de alerta por email
     */
    public function sendAlertEmail(MonitoringAlertsHistory $alert, array $emails): void
    {
        try {
            $tenant = $alert->tenant;
            $subject = $this->getAlertEmailSubject($alert);

            // Criar registro de notificação no banco
            $notification = Notification::create([
                'tenant_id' => $alert->tenant_id,
                'type' => 'alert_email',
                'email' => implode(',', $emails),
                'subject' => $subject,
                'message' => $this->getAlertEmailMessage($alert),
                'sent_at' => now(),
            ]);

            // Enviar email usando fila
            Mail::to($emails)->queue(new AlertNotificationMail($alert, $subject));

            Log::info('Notificação de alerta por email enviada', [
                'alert_id' => $alert->id,
                'emails' => $emails,
                'notification_id' => $notification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação de alerta por email', [
                'alert_id' => $alert->id,
                'emails' => $emails,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envia notificação de alerta para Slack
     */
    public function sendSlackAlert(MonitoringAlertsHistory $alert, string $webhookUrl): void
    {
        try {
            $payload = $this->buildSlackPayload($alert);

            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('Notificação de alerta para Slack enviada', [
                    'alert_id' => $alert->id,
                    'webhook_url' => $webhookUrl,
                ]);
            } else {
                Log::error('Falha ao enviar notificação para Slack', [
                    'alert_id' => $alert->id,
                    'webhook_url' => $webhookUrl,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação de alerta para Slack', [
                'alert_id' => $alert->id,
                'webhook_url' => $webhookUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envia notificação de alerta por SMS (placeholder para implementação futura)
     */
    public function sendAlertSms(MonitoringAlertsHistory $alert, string $phoneNumber): void
    {
        // Implementar integração com serviço de SMS (Twilio, etc.)
        Log::info('Notificação de alerta por SMS não implementada', [
            'alert_id' => $alert->id,
            'phone_number' => $phoneNumber,
        ]);
    }

    /**
     * Monta o payload para Slack
     */
    private function buildSlackPayload(MonitoringAlertsHistory $alert): array
    {
        $severityColor = $this->getSlackColor($alert->severity);
        $severityEmoji = $this->getSlackEmoji($alert->severity);

        return [
            'attachments' => [
                [
                    'color' => $severityColor,
                    'title' => "{$severityEmoji} Alerta de {$alert->getSeverityLabel()} - {$alert->getAlertTypeLabel()}",
                    'fields' => [
                        [
                            'title' => 'Métrica',
                            'value' => $alert->metric_name,
                            'short' => true,
                        ],
                        [
                            'title' => 'Valor Atual',
                            'value' => number_format((float) $alert->metric_value, 2),
                            'short' => true,
                        ],
                        [
                            'title' => 'Limiar',
                            'value' => number_format((float) $alert->threshold_value, 2),
                            'short' => true,
                        ],
                        [
                            'title' => 'Tenant',
                            'value' => $alert->tenant->name,
                            'short' => true,
                        ],
                        [
                            'title' => 'Horário',
                            'value' => $alert->created_at->format('d/m/Y H:i:s'),
                            'short' => false,
                        ],
                        [
                            'title' => 'Mensagem',
                            'value' => $alert->message,
                            'short' => false,
                        ],
                    ],
                    'footer' => 'EasyBudget Alert System',
                    'ts' => $alert->created_at->timestamp,
                ],
            ],
        ];
    }

    /**
     * Obtém a cor para Slack baseada na severidade
     */
    private function getSlackColor(string $severity): string
    {
        return match ($severity) {
            'critical' => 'danger',
            'error' => 'warning',
            'warning' => '#f59e0b',
            'info' => '#3b82f6',
            default => '#6b7280',
        };
    }

    /**
     * Obtém o emoji para Slack baseado na severidade
     */
    private function getSlackEmoji(string $severity): string
    {
        return match ($severity) {
            'critical' => '🚨',
            'error' => '⚠️',
            'warning' => '⚡',
            'info' => 'ℹ️',
            default => '📊',
        };
    }

    /**
     * Monta o assunto do email
     */
    private function getAlertEmailSubject(MonitoringAlertsHistory $alert): string
    {
        $tenantName = $alert->tenant->name;
        $severityLabel = $alert->getSeverityLabel();
        $typeLabel = $alert->getAlertTypeLabel();

        return "[EasyBudget] Alerta {$severityLabel} - {$typeLabel} - {$tenantName}";
    }

    /**
     * Monta a mensagem do email
     */
    private function getAlertEmailMessage(MonitoringAlertsHistory $alert): string
    {
        return $alert->message;
    }

    /**
     * Cria notificação no banco de dados
     */
    public function createNotification(
        int $tenantId,
        string $type,
        string $email,
        string $subject,
        string $message,
        ?Carbon $sentAt = null
    ): Notification {
        return Notification::create([
            'tenant_id' => $tenantId,
            'type' => $type,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'sent_at' => $sentAt ?? now(),
        ]);
    }
}
