<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AlertSeverityEnum;
use App\Enums\AlertTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\AlertSetting;
use App\Models\MonitoringAlertsHistory;
use App\Services\AlertService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function __construct(
        private AlertService $alertService
    ) {}

    /**
     * Dashboard de alertas
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Obter estatísticas dos últimos 30 dias
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();
        
        $statistics = $this->alertService->getAlertStatistics($tenantId, $startDate, $endDate);
        
        // Obter alertas não resolvidos
        $unresolvedAlerts = $this->alertService->getUnresolvedAlerts($tenantId, [
            'date_from' => $request->input('date_from', $startDate),
            'date_to' => $request->input('date_to', $endDate),
        ]);

        // Obter alertas recentes (últimas 24 horas)
        $recentAlerts = MonitoringAlertsHistory::where('tenant_id', $tenantId)
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Contadores por severidade
        $severityCounts = [
            'critical' => $unresolvedAlerts->where('severity', AlertSeverityEnum::CRITICAL->value)->count(),
            'error' => $unresolvedAlerts->where('severity', AlertSeverityEnum::ERROR->value)->count(),
            'warning' => $unresolvedAlerts->where('severity', AlertSeverityEnum::WARNING->value)->count(),
            'info' => $unresolvedAlerts->where('severity', AlertSeverityEnum::INFO->value)->count(),
        ];

        return view('admin.alerts.index', compact(
            'statistics',
            'unresolvedAlerts',
            'recentAlerts',
            'severityCounts'
        ));
    }

    /**
     * Configurações de alertas
     */
    public function settings(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $alertSettings = AlertSetting::where('tenant_id', $tenantId)
            ->orderBy('alert_type')
            ->orderBy('metric_name')
            ->get()
            ->groupBy('alert_type');

        $alertTypes = AlertTypeEnum::cases();
        $severities = AlertSeverityEnum::cases();

        return view('admin.alerts.settings', compact(
            'alertSettings',
            'alertTypes',
            'severities'
        ));
    }

    /**
     * Criar nova configuração de alerta
     */
    public function createSetting()
    {
        $alertTypes = AlertTypeEnum::cases();
        $severities = AlertSeverityEnum::cases();
        
        // Métricas disponíveis por tipo
        $availableMetrics = [
            'performance' => [
                'response_time' => 'Tempo de Resposta (ms)',
                'database_queries' => 'Queries de Banco de Dados',
                'cache_hit_rate' => 'Taxa de Acerto de Cache (%)',
            ],
            'security' => [
                'failed_login_attempts' => 'Tentativas de Login Falhadas',
                'suspicious_activity' => 'Atividades Suspeitas',
                'unauthorized_access' => 'Acessos Não Autorizados',
            ],
            'availability' => [
                'uptime' => 'Uptime (%)',
                'error_5xx' => 'Erros 5xx',
                'error_4xx' => 'Erros 4xx',
            ],
            'resource' => [
                'memory_usage' => 'Uso de Memória (MB)',
                'cpu_usage' => 'Uso de CPU (%)',
                'disk_usage' => 'Uso de Disco (%)',
            ],
            'business' => [
                'daily_revenue' => 'Receita Diária',
                'failed_payments' => 'Pagamentos Falhados',
                'expired_budgets' => 'Orçamentos Vencidos',
            ],
            'system' => [
                'queue_size' => 'Tamanho da Fila',
                'backup_status' => 'Status do Backup',
                'log_errors' => 'Erros no Log',
            ],
        ];

        return view('admin.alerts.create-setting', compact(
            'alertTypes',
            'severities',
            'availableMetrics'
        ));
    }

    /**
     * Salvar nova configuração de alerta
     */
    public function storeSetting(Request $request)
    {
        $validated = $request->validate([
            'alert_type' => 'required|string|in:' . implode(',', array_column(AlertTypeEnum::cases(), 'value')),
            'metric_name' => 'required|string|max:100',
            'severity' => 'required|string|in:' . implode(',', array_column(AlertSeverityEnum::cases(), 'value')),
            'threshold_value' => 'required|numeric|min:0',
            'evaluation_window_minutes' => 'required|integer|min:1|max:1440',
            'cooldown_minutes' => 'required|integer|min:1|max:10080',
            'is_active' => 'boolean',
            'notification_channels' => 'array',
            'notification_channels.*' => 'in:email,sms,slack',
            'notification_emails' => 'array',
            'notification_emails.*' => 'email',
            'slack_webhook_url' => 'nullable|url|max:500',
            'custom_message' => 'nullable|string|max:500',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['notification_channels'] = $request->input('notification_channels', ['email']);
        $validated['notification_emails'] = $request->input('notification_emails', []);

        AlertSetting::create($validated);

        return redirect()->route('admin.alerts.settings')
            ->with('success', 'Configuração de alerta criada com sucesso!');
    }

    /**
     * Editar configuração de alerta
     */
    public function editSetting(AlertSetting $alertSetting)
    {
        $this->authorize('update', $alertSetting);

        $alertTypes = AlertTypeEnum::cases();
        $severities = AlertSeverityEnum::cases();
        
        // Métricas disponíveis por tipo (mesmo do create)
        $availableMetrics = [
            'performance' => [
                'response_time' => 'Tempo de Resposta (ms)',
                'database_queries' => 'Queries de Banco de Dados',
                'cache_hit_rate' => 'Taxa de Acerto de Cache (%)',
            ],
            'security' => [
                'failed_login_attempts' => 'Tentativas de Login Falhadas',
                'suspicious_activity' => 'Atividades Suspeitas',
                'unauthorized_access' => 'Acessos Não Autorizados',
            ],
            'availability' => [
                'uptime' => 'Uptime (%)',
                'error_5xx' => 'Erros 5xx',
                'error_4xx' => 'Erros 4xx',
            ],
            'resource' => [
                'memory_usage' => 'Uso de Memória (MB)',
                'cpu_usage' => 'Uso de CPU (%)',
                'disk_usage' => 'Uso de Disco (%)',
            ],
            'business' => [
                'daily_revenue' => 'Receita Diária',
                'failed_payments' => 'Pagamentos Falhados',
                'expired_budgets' => 'Orçamentos Vencidos',
            ],
            'system' => [
                'queue_size' => 'Tamanho da Fila',
                'backup_status' => 'Status do Backup',
                'log_errors' => 'Erros no Log',
            ],
        ];

        return view('admin.alerts.edit-setting', compact(
            'alertSetting',
            'alertTypes',
            'severities',
            'availableMetrics'
        ));
    }

    /**
     * Atualizar configuração de alerta
     */
    public function updateSetting(Request $request, AlertSetting $alertSetting)
    {
        $this->authorize('update', $alertSetting);

        $validated = $request->validate([
            'alert_type' => 'required|string|in:' . implode(',', array_column(AlertTypeEnum::cases(), 'value')),
            'metric_name' => 'required|string|max:100',
            'severity' => 'required|string|in:' . implode(',', array_column(AlertSeverityEnum::cases(), 'value')),
            'threshold_value' => 'required|numeric|min:0',
            'evaluation_window_minutes' => 'required|integer|min:1|max:1440',
            'cooldown_minutes' => 'required|integer|min:1|max:10080',
            'is_active' => 'boolean',
            'notification_channels' => 'array',
            'notification_channels.*' => 'in:email,sms,slack',
            'notification_emails' => 'array',
            'notification_emails.*' => 'email',
            'slack_webhook_url' => 'nullable|url|max:500',
            'custom_message' => 'nullable|string|max:500',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['notification_channels'] = $request->input('notification_channels', ['email']);
        $validated['notification_emails'] = $request->input('notification_emails', []);

        $alertSetting->update($validated);

        return redirect()->route('admin.alerts.settings')
            ->with('success', 'Configuração de alerta atualizada com sucesso!');
    }

    /**
     * Excluir configuração de alerta
     */
    public function destroySetting(AlertSetting $alertSetting)
    {
        $this->authorize('delete', $alertSetting);

        $alertSetting->delete();

        return redirect()->route('admin.alerts.settings')
            ->with('success', 'Configuração de alerta excluída com sucesso!');
    }

    /**
     * Toggle ativação da configuração de alerta
     */
    public function toggleSetting(AlertSetting $alertSetting)
    {
        $this->authorize('update', $alertSetting);

        $alertSetting->update(['is_active' => !$alertSetting->is_active]);

        return redirect()->route('admin.alerts.settings')
            ->with('success', 'Configuração de alerta ' . ($alertSetting->is_active ? 'ativada' : 'desativada') . ' com sucesso!');
    }

    /**
     * Histórico de alertas
     */
    public function history(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $query = MonitoringAlertsHistory::where('tenant_id', $tenantId)
            ->with(['alertSetting', 'resolvedBy'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('type')) {
            $query->where('alert_type', $request->input('type'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'resolved') {
                $query->where('is_resolved', true);
            } elseif ($request->input('status') === 'unresolved') {
                $query->where('is_resolved', false);
            }
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        $alerts = $query->paginate(20);

        $alertTypes = AlertTypeEnum::cases();
        $severities = AlertSeverityEnum::cases();

        return view('admin.alerts.history', compact(
            'alerts',
            'alertTypes',
            'severities'
        ));
    }

    /**
     * Resolver alerta
     */
    public function resolveAlert(MonitoringAlertsHistory $alert, Request $request)
    {
        $this->authorize('update', $alert);

        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $alert->markAsResolved(auth()->id(), $validated['resolution_notes'] ?? null);

        return redirect()->back()
            ->with('success', 'Alerta resolvido com sucesso!');
    }

    /**
     * Estatísticas de alertas (API)
     */
    public function statistics(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $statistics = $this->alertService->getAlertStatistics(
            $tenantId,
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        );

        return response()->json($statistics);
    }
}