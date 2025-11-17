<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AlertSeverityEnum;
use App\Enums\AlertTypeEnum;
use App\Models\AlertSetting;
use App\Models\MonitoringAlertsHistory;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AlertService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Avalia uma métrica e gera alertas se necessário
     */
    public function evaluateMetric(
        int $tenantId,
        AlertTypeEnum $type,
        string $metricName,
        float $metricValue,
        array $additionalData = []
    ): ?MonitoringAlertsHistory {
        try {
            // Buscar configurações de alerta ativas para este tipo e métrica
            $alertSettings = AlertSetting::where('tenant_id', $tenantId)
                ->where('alert_type', $type->value)
                ->where('metric_name', $metricName)
                ->where('is_active', true)
                ->get();

            if ($alertSettings->isEmpty()) {
                return null;
            }

            // Avaliar cada configuração de alerta
            foreach ($alertSettings as $setting) {
                $alert = $this->checkThreshold($setting, $metricValue, $additionalData);
                
                if ($alert) {
                    return $alert;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao avaliar métrica para alertas', [
                'tenant_id' => $tenantId,
                'type' => $type->value,
                'metric_name' => $metricName,
                'metric_value' => $metricValue,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Verifica se uma métrica ultrapassa o limiar configurado
     */
    private function checkThreshold(
        AlertSetting $setting,
        float $metricValue,
        array $additionalData
    ): ?MonitoringAlertsHistory {
        try {
            // Verificar se está em período de cooldown
            if ($setting->isInCooldown()) {
                return null;
            }

            $threshold = (float) $setting->threshold_value;
            $shouldAlert = false;
            $message = '';

            // Lógica de comparação baseada no tipo de métrica
            switch ($setting->metric_name) {
                case 'response_time':
                case 'memory_usage':
                case 'cpu_usage':
                case 'disk_usage':
                    // Métricas que devem estar abaixo do limiar
                    $shouldAlert = $metricValue > $threshold;
                    $message = "Métrica '{$setting->metric_name}' está acima do limiar: {$metricValue} > {$threshold}";
                    break;

                case 'uptime':
                case 'success_rate':
                    // Métricas que devem estar acima do limiar
                    $shouldAlert = $metricValue < $threshold;
                    $message = "Métrica '{$setting->metric_name}' está abaixo do limiar: {$metricValue} < {$threshold}";
                    break;

                default:
                    // Para métricas customizadas, usar lógica padrão
                    $shouldAlert = $metricValue > $threshold;
                    $message = "Métrica '{$setting->metric_name}' atingiu valor: {$metricValue} (limiar: {$threshold})";
                    break;
            }

            if (!$shouldAlert) {
                return null;
            }

            // Criar o alerta
            $alert = MonitoringAlertsHistory::create([
                'tenant_id' => $setting->tenant_id,
                'alert_setting_id' => $setting->id,
                'alert_type' => $setting->alert_type,
                'severity' => $setting->severity,
                'metric_name' => $setting->metric_name,
                'metric_value' => $metricValue,
                'threshold_value' => $threshold,
                'message' => $setting->custom_message ?? $message,
                'additional_data' => $additionalData,
                'is_resolved' => false,
                'notification_sent' => false,
            ]);

            // Enviar notificação se configurado
            if ($setting->shouldNotify() && !$alert->notification_sent) {
                $this->sendNotification($alert, $setting);
            }

            Log::warning('Alerta gerado', [
                'tenant_id' => $setting->tenant_id,
                'alert_id' => $alert->id,
                'type' => $setting->alert_type,
                'severity' => $setting->severity,
                'metric' => $setting->metric_name,
                'value' => $metricValue,
                'threshold' => $threshold
            ]);

            return $alert;

        } catch (\Exception $e) {
            Log::error('Erro ao verificar limiar de alerta', [
                'setting_id' => $setting->id,
                'metric_value' => $metricValue,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Envia notificações para o alerta
     */
    private function sendNotification(MonitoringAlertsHistory $alert, AlertSetting $setting): void
    {
        try {
            $channels = $setting->notification_channels ?? ['email'];
            
            foreach ($channels as $channel) {
                switch ($channel) {
                    case 'email':
                        $this->sendEmailNotification($alert, $setting);
                        break;
                    
                    case 'slack':
                        $this->sendSlackNotification($alert, $setting);
                        break;
                    
                    case 'sms':
                        // Implementar SMS se necessário
                        break;
                }
            }

            $alert->markAsNotified();

        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação de alerta', [
                'alert_id' => $alert->id,
                'channels' => $channels,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envia notificação por email
     */
    private function sendEmailNotification(MonitoringAlertsHistory $alert, AlertSetting $setting): void
    {
        $emails = $setting->notification_emails ?? [];
        
        if (empty($emails)) {
            // Buscar emails dos administradores do tenant
            $tenant = Tenant::find($alert->tenant_id);
            if ($tenant) {
                $emails = $tenant->users()
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'admin');
                    })
                    ->pluck('email')
                    ->toArray();
            }
        }

        if (!empty($emails)) {
            $this->notificationService->sendAlertEmail($alert, $emails);
        }
    }

    /**
     * Envia notificação para Slack
     */
    private function sendSlackNotification(MonitoringAlertsHistory $alert, AlertSetting $setting): void
    {
        $webhookUrl = $setting->slack_webhook_url;
        
        if ($webhookUrl) {
            $this->notificationService->sendSlackAlert($alert, $webhookUrl);
        }
    }

    /**
     * Obtém alertas não resolvidos para um tenant
     */
    public function getUnresolvedAlerts(int $tenantId, array $filters = []): Collection
    {
        $query = MonitoringAlertsHistory::where('tenant_id', $tenantId)
            ->where('is_resolved', false);

        if (!empty($filters['type'])) {
            $query->where('alert_type', $filters['type']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtém estatísticas de alertas para um período
     */
    public function getAlertStatistics(int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $stats = MonitoringAlertsHistory::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_alerts,
                COUNT(CASE WHEN is_resolved = true THEN 1 END) as resolved_alerts,
                COUNT(CASE WHEN severity = ? THEN 1 END) as critical_alerts,
                COUNT(CASE WHEN severity = ? THEN 1 END) as error_alerts,
                COUNT(CASE WHEN severity = ? THEN 1 END) as warning_alerts,
                COUNT(CASE WHEN severity = ? THEN 1 END) as info_alerts
            ', [
                AlertSeverityEnum::CRITICAL->value,
                AlertSeverityEnum::ERROR->value,
                AlertSeverityEnum::WARNING->value,
                AlertSeverityEnum::INFO->value,
            ])
            ->first();

        return [
            'total_alerts' => $stats->total_alerts ?? 0,
            'resolved_alerts' => $stats->resolved_alerts ?? 0,
            'unresolved_alerts' => ($stats->total_alerts ?? 0) - ($stats->resolved_alerts ?? 0),
            'critical_alerts' => $stats->critical_alerts ?? 0,
            'error_alerts' => $stats->error_alerts ?? 0,
            'warning_alerts' => $stats->warning_alerts ?? 0,
            'info_alerts' => $stats->info_alerts ?? 0,
            'resolution_rate' => $stats->total_alerts > 0 
                ? round((($stats->resolved_alerts ?? 0) / $stats->total_alerts) * 100, 2)
                : 0,
        ];
    }

    /**
     * Resolve um alerta
     */
    public function resolveAlert(MonitoringAlertsHistory $alert, int $resolvedBy, string $resolutionNotes = null): bool
    {
        return $alert->markAsResolved($resolvedBy, $resolutionNotes);
    }

    /**
     * Limpa alertas antigos (mais de 90 dias)
     */
    public function cleanupOldAlerts(int $daysToKeep = 90): int
    {
        return MonitoringAlertsHistory::where('created_at', '<', now()->subDays($daysToKeep))
            ->where('is_resolved', true)
            ->delete();
    }
}