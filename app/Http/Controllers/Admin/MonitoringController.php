<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\MonitoringAlertHistory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MonitoringController extends Controller
{
    public function dashboard(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $tenantId = (int)($user->tenant_id ?? 0);

        $totalAlerts = MonitoringAlertHistory::where('tenant_id', $tenantId)->count();
        $unresolved = MonitoringAlertHistory::where('tenant_id', $tenantId)->where('is_resolved', false)->count();
        $critical = MonitoringAlertHistory::where('tenant_id', $tenantId)->whereIn('severity', ['high','critical'])->count();
        $recent = MonitoringAlertHistory::where('tenant_id', $tenantId)->latest('created_at')->limit(10)->get();

        $stats = [
            'total_alerts' => $totalAlerts,
            'unresolved_alerts' => $unresolved,
            'critical_alerts' => $critical,
            'recent_alerts' => $recent,
        ];

        return view('pages.admin.monitoring.dashboard', compact('stats'));
    }

    public function metrics(): View
    {
        return view('pages.admin.monitoring.metrics');
    }

    public function middlewareMetrics(): View
    {
        return view('pages.admin.monitoring.middleware');
    }

    public function apiMetrics(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $tenantId = (int)($user->tenant_id ?? 0);

        $byType = MonitoringAlertHistory::where('tenant_id', $tenantId)
            ->selectRaw('alert_type, COUNT(*) as count')
            ->groupBy('alert_type')
            ->get()
            ->mapWithKeys(fn($r) => [$r->alert_type => (int)$r->count])
            ->all();

        $bySeverity = MonitoringAlertHistory::where('tenant_id', $tenantId)
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->get()
            ->mapWithKeys(fn($r) => [$r->severity => (int)$r->count])
            ->all();

        $recent = MonitoringAlertHistory::where('tenant_id', $tenantId)
            ->latest('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'by_type' => $byType,
            'by_severity' => $bySeverity,
            'recent' => $recent,
        ]);
    }
}