<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\AuditLog;
use App\Services\Application\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AuditController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {}

    /**
     * Display audit logs
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'user_id', 'action', 'date_from', 'date_to']);
        $result = $this->auditLogService->getFilteredLogs($filters, 50);

        $logs = $result->getData();
        $search = $filters['search'] ?? null;
        $user = $filters['user_id'] ?? null;
        $action = $filters['action'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        return view('admin.audit.index', compact('logs', 'search', 'user', 'action', 'dateFrom', 'dateTo'));
    }

    /**
     * Display specific audit log
     */
    public function show(AuditLog $log): View
    {
        $log->load(['user']);

        return view('admin.audit.show', compact('log'));
    }

    /**
     * Export audit logs
     */
    public function export(Request $request): mixed
    {
        $format = $request->get('format', 'excel');

        $result = $this->auditLogService->prepareExportData($request->all());

        if (! $result->isSuccess()) {
            return back()->with('error', $result->getMessage());
        }

        $exportData = $result->getData();

        if ($format === 'excel') {
            // Aqui deveríamos ter uma classe de exportação do Excel,
            // mas para manter a compatibilidade com o que existia:
            return Excel::download(new \App\Exports\AuditLogsExport($exportData), 'audit_logs.xlsx');
        } elseif ($format === 'csv') {
            return Excel::download(new \App\Exports\AuditLogsExport($exportData), 'audit_logs.csv');
        }

        return back()->with('error', 'Formato de exportação não suportado.');
    }

    /**
     * Delete audit log
     */
    public function destroy(AuditLog $log): RedirectResponse
    {
        $log->delete();

        return redirect()->route('admin.audit.index')
            ->with('success', 'Log de auditoria excluído com sucesso!');
    }
}
