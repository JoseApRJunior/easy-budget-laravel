<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\AuditLog;
use App\Services\Domain\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AuditController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * Display audit logs
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');
        $user = $request->get('user');
        $action = $request->get('action');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = AuditLog::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('old_values', 'like', "%{$search}%")
                    ->orWhere('new_values', 'like', "%{$search}%");
            });
        }

        if ($user) {
            $query->where('user_id', $user);
        }

        if ($action) {
            $query->where('event', $action);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->with(['user'])
            ->latest()
            ->paginate(50)
            ->appends($request->query());

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

        try {
            $exportData = $this->auditService->prepareExportData($request->all());

            if ($format === 'excel') {
                return Excel::download($exportData, 'audit_logs.xlsx');
            } elseif ($format === 'csv') {
                return Excel::download($exportData, 'audit_logs.csv');
            } elseif ($format === 'pdf') {
                return $this->auditService->generatePdfExport($exportData);
            }

            return back()->with('error', 'Formato de exportação não suportado.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao exportar logs: '.$e->getMessage());
        }
    }

    /**
     * Delete audit log
     */
    public function destroy(AuditLog $log): RedirectResponse
    {
        try {
            $log->delete();

            return redirect()->route('admin.audit.logs')
                ->with('success', 'Log de auditoria excluído com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir log: '.$e->getMessage());
        }
    }
}
