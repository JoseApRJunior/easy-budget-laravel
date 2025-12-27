<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Domain\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class SystemReportsController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * Display system reports dashboard
     */
    public function index(Request $request): View
    {
        $reports = [
            'financial' => $this->reportService->getFinancialSummary(),
            'users' => $this->reportService->getUserStats(),
            'tenants' => $this->reportService->getTenantStats(),
            'plans' => $this->reportService->getPlanStats(),
            'system' => $this->reportService->getSystemStats(),
        ];

        return view('admin.reports.index', compact('reports'));
    }

    /**
     * Display financial reports
     */
    public function financial(Request $request): View
    {
        $dateRange = $request->get('date_range', 'last_30_days');
        $reportData = $this->reportService->generateFinancialReport($dateRange);

        return view('admin.reports.financial', compact('reportData', 'dateRange'));
    }

    /**
     * Display user reports
     */
    public function users(Request $request): View
    {
        $dateRange = $request->get('date_range', 'last_30_days');
        $reportData = $this->reportService->generateUserReport($dateRange);

        return view('admin.reports.users', compact('reportData', 'dateRange'));
    }

    /**
     * Display tenant reports
     */
    public function tenants(Request $request): View
    {
        $dateRange = $request->get('date_range', 'last_30_days');
        $reportData = $this->reportService->generateTenantReport($dateRange);

        return view('admin.reports.tenants', compact('reportData', 'dateRange'));
    }

    /**
     * Display plan reports
     */
    public function plans(Request $request): View
    {
        $dateRange = $request->get('date_range', 'last_30_days');
        $reportData = $this->reportService->generatePlanReport($dateRange);

        return view('admin.reports.plans', compact('reportData', 'dateRange'));
    }

    /**
     * Display system reports
     */
    public function system(Request $request): View
    {
        $dateRange = $request->get('date_range', 'last_30_days');
        $reportData = $this->reportService->generateSystemReport($dateRange);

        return view('admin.reports.system', compact('reportData', 'dateRange'));
    }

    /**
     * Export report
     */
    public function export(Request $request, string $type, string $format): RedirectResponse
    {
        $dateRange = $request->get('date_range', 'last_30_days');

        $exportData = $this->reportService->prepareExportData($type, $dateRange);

        if ($format === 'excel') {
            return Excel::download($exportData, "{$type}_report.xlsx");
        } elseif ($format === 'pdf') {
            return $this->reportService->generatePdfExport($exportData, $type);
        } elseif ($format === 'csv') {
            return Excel::download($exportData, "{$type}_report.csv");
        }

        return back()->with('error', 'Formato de exportação não suportado.');
    }

    /**
     * Generate custom report
     */
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'report_type' => 'required|string|in:financial,users,tenants,plans,system',
            'date_range' => 'required|string|in:today,yesterday,last_7_days,last_30_days,last_month,last_quarter,last_year,custom',
            'start_date' => 'required_if:date_range,custom|date',
            'end_date' => 'required_if:date_range,custom|date|after_or_equal:start_date',
            'format' => 'required|string|in:html,pdf,excel,csv',
        ]);

        $reportData = $this->reportService->generateCustomReport($validated);

        if ($validated['format'] === 'html') {
            return redirect()->route("admin.reports.{$validated['report_type']}", [
                'date_range' => $validated['date_range'],
            ]);
        }

        return $this->export($request, $validated['report_type'], $validated['format']);
    }
}
