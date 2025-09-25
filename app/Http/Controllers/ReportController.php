<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Services\ActivityService;
use App\Services\BudgetService;
use App\Services\ActivityService;
use App\Services\CustomerService;
use App\Services\ActivityService;
use App\Services\InvoiceService;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BudgetReportExport;
use App\Exports\FinancialReportExport;
use App\Exports\CustomerReportExport;

/**
 * Controlador para geração de relatórios.
 * Implementa diversos tipos de relatórios com exportação PDF/Excel.
 * Migração do sistema legacy app/controllers/ReportController.php.
 *
 * @package App\Http\Controllers
 * @author IA
 */
class ReportController extends BaseController
{
    /**
     * @var ReportService
     */
    protected ReportService $reportService;

    /**
     * @var BudgetService
     */
    protected BudgetService $budgetService;

    /**
     * @var CustomerService
     */
    protected CustomerService $customerService;

    /**
     * @var InvoiceService
     */
    protected InvoiceService $invoiceService;

    /**
     * Construtor da classe ReportController.
     *
     * @param ReportService $reportService
     * @param BudgetService $budgetService
     * @param CustomerService $customerService
     * @param InvoiceService $invoiceService
     */
    public function __construct(
        ReportService $reportService,
        BudgetService $budgetService,
        CustomerService $customerService,
        InvoiceService $invoiceService
    ) {
        parent::__construct($activityService);
        $this->reportService = $reportService;
        $this->budgetService = $budgetService;
        $this->customerService = $customerService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Exibe a página principal de relatórios com opções.
     *
     * @return View
     */
    public function index(): View
    {
        $tenantId = $this->tenantId();

        if (!$tenantId) {
            return $this->errorRedirect('Tenant não encontrado.');
        }

        $this->logActivity(
            action: 'view_reports',
            entity: 'reports',
            metadata: ['tenant_id' => $tenantId]
        );

        $reportTypes = [
            'budget' => 'Relatórios de Orçamentos',
            'financial' => 'Relatórios Financeiros',
            'customer' => 'Relatórios de Clientes',
            'product' => 'Relatórios de Produtos',
            'provider' => 'Relatórios de Prestadores',
            'activity' => 'Relatórios de Atividades'
        ];

        $recentReports = $this->reportService->getRecentReports($tenantId, 10);

        return $this->renderView('reports.index', [
            'reportTypes' => $reportTypes,
            'recentReports' => $recentReports,
            'tenantId' => $tenantId
        ]);
    }

    /**
     * Gera relatório de orçamentos.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function budgetReport(Request $request): View|RedirectResponse
    {
        $tenantId = $this->tenantId();

        if (!$tenantId) {
            return $this->errorRedirect('Tenant não encontrado.');
        }

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'status' => 'nullable|array',
            'status.*' => 'in:draft,sent,approved,rejected,completed,cancelled',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'provider_id' => 'nullable|integer|exists:providers,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'format' => 'nullable|in:html,pdf,excel'
        ]);

        $this->logActivity(
            action: 'generate_budget_report',
            entity: 'reports',
            metadata: [
                'tenant_id' => $tenantId,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'format' => $request->get('format', 'html')
            ]
        );

        $reportData = $this->reportService->generateBudgetReport(
            tenantId: $tenantId,
            dateFrom: $request->date_from,
            dateTo: $request->date_to,
            filters: $request->only(['status', 'customer_id', 'provider_id', 'category_id'])
        );

        if ($request->get('format') === 'pdf') {
            $pdf = PDF::loadView('reports.budget', $reportData);
            $filename = 'relatorio_orcamentos_' . date('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        }

        if ($request->get('format') === 'excel') {
            return Excel::download(new BudgetReportExport($reportData),
                'relatorio_orcamentos_' . date('Y-m-d') . '.xlsx');
        }

        return $this->renderView('reports.budget', $reportData);
    }

    /**
     * Gera relatório financeiro consolidado.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function financialReport(Request $request): View|RedirectResponse
    {
        $tenantId = $this->tenantId();

        if (!$tenantId) {
            return $this->errorRedirect('Tenant não encontrado.');
        }

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'report_type' => 'required|in:revenue,expenses,profit,all',
            'payment_method' => 'nullable|array',
            'payment_method.*' => 'in:cash,credit_card,debit_card,bank_transfer,pix,boleto',
            'format' => 'nullable|in:html,pdf,excel'
        ]);

        $this->logActivity(
            action: 'generate_financial_report',
            entity: 'reports',
            metadata: [
                'tenant_id' => $tenantId,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'report_type' => $request->report_type,
                'format' => $request->get('format', 'html')
            ]
        );

        $reportData = $this->reportService->generateFinancialReport(
            tenantId: $tenantId,
            dateFrom: $request->date_from,
            dateTo: $request->date_to,
            reportType: $request->report_type,
            filters: $request->only(['payment_method'])
        );

        if ($request->get('format') === 'pdf') {
            $pdf = PDF::loadView('reports.financial', $reportData);
            $filename = 'relatorio_financeiro_' . $request->report_type . '_' . date('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        }

        if ($request->get('format') === 'excel') {
            return Excel::download(new FinancialReportExport($reportData),
                'relatorio_financeiro_' . $request->report_type . '_' . date('Y-m-d') . '.xlsx');
        }

        return $this->renderView('reports.financial', $reportData);
    }

    /**
     * Gera relatório de clientes.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function customerReport(Request $request): View|RedirectResponse
    {
        $tenantId = $this->tenantId();

        if (!$tenantId) {
            return $this->errorRedirect('Tenant não encontrado.');
        }

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'report_type' => 'required|in:activity,total_spent,budgets_count,invoices_count',
            'status' => 'nullable|array',
            'status.*' => 'in:active,inactive',
            'format' => 'nullable|in:html,pdf,excel'
        ]);

        $this->logActivity(
            action: 'generate_customer_report',
            entity: 'reports',
            metadata: [
                'tenant_id' => $tenantId,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'report_type' => $request->report_type,
                'format' => $request->get('format', 'html')
            ]
        );

        $reportData = $this->reportService->generateCustomerReport(
            tenantId: $tenantId,
            dateFrom: $request->date_from,
            dateTo: $request->date_to,
            reportType: $request->report_type,
            filters: $request->only(['status'])
        );

        if ($request->get('format') === 'pdf') {
            $pdf = PDF::loadView('reports.customers', $reportData);
            $filename = 'relatorio_clientes_' . $request->report_type . '_' . date('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        }

        if ($request->get('format') === 'excel') {
            return Excel::download(new CustomerReportExport($reportData),
                'relatorio_clientes_' . $request->report_type . '_' . date('Y-m-d') . '.xlsx');
        }

        return $this->renderView('reports.customers', $reportData);
    }

    /**
     * Gera relatório de produtos.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function productReport(Request $request): View|RedirectResponse
    {
        $tenantId = $this->tenantId();

        if (!$tenantId) {
            return $this->errorRedirect('Tenant não encontrado.');
        }

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'report_type' => 'required|in:sales,stock,inventory_value,top_sellers',
            'category_id' => 'nullable|integer|exists:categories,id',
            'format' => 'nullable|in:html,pdf,excel'
        ]);

        $this->logActivity(
            action: 'generate_product_report',
            entity: 'reports',
            metadata: [
                'tenant_id' => $tenantId,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'report_type' => $request->report_type,
                'format' => $request->get('format', 'html')
            ]
        );

        $reportData = $this->reportService->generateProductReport(
            tenantId: $tenantId,
            dateFrom: $request->date_from,
            dateTo: $request->date_to,
            reportType: $request->report_type,
            categoryId: $request->category_id
        );

        if ($request->get('format') === 'pdf') {
            $pdf = PDF::loadView('reports.products', $reportData);
            $filename = 'relatorio_produtos_' . $request->report_type . '_' . date('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        }

        if ($request->get('format') === 'excel') {
            // Implementar exportação Excel específica para produtos
            $filename = 'relatorio_produtos_' . $request->report_type . '_' . date('Y-m-d') . '.xlsx';
            // return Excel::download(new ProductReportExport($reportData), $filename);
            return $this->renderView('reports.products', $reportData); // Placeholder
        }

        return $this->renderView('reports.products', $reportData);
    }

    /**
     * Gera relatório de atividades do sistema.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function activityReport(Request $request): View|RedirectResponse
    {
        $tenantId = $this->tenantId();

        if (!$tenantId) {
            return $this->errorRedirect('Tenant não encontrado.');
        }

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'entity_type' => 'nullable|string|max:50',
            'action_type' => 'nullable|string|max:50',
            'user_id' => 'nullable|integer|exists:users,id',
            'format' => 'nullable|in:html,pdf,excel'
        ]);

        $this->logActivity(
            action: 'generate_activity_report',
            entity: 'reports',
            metadata: [
                'tenant_id' => $tenantId,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'format' => $request->get('format', 'html')
            ]
        );

        $reportData = $this->reportService->generateActivityReport(
            tenantId: $tenantId,
            dateFrom: $request->date_from,
            dateTo: $request->date_to,
            filters: $request->only(['entity_type', 'action_type', 'user_id'])
        );

        if ($request->get('format') === 'pdf') {
            $pdf = PDF::loadView('reports.activities', $reportData);
            $filename = 'relatorio_atividades_' . date('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        }

        if ($request->get('format') === 'excel') {
            // Implementar exportação Excel para atividades
            $filename = 'relatorio_atividades_' . date('Y-m-d') . '.xlsx';
            // return Excel::download(new ActivityReportExport($reportData), $filename);
            return $this->renderView('reports.activities', $reportData); // Placeholder
        }

        return $this->renderView('reports.activities', $reportData);
    }

    /**
     * Dashboard de métricas de relatórios.
     *
     * @return View
     */
    public function metricsDashboard(): View
    {
        $tenantId = $this->tenantId();

        if (!$tenantId) {
            return $this->errorRedirect('Tenant não encontrado.');
        }

        $this->logActivity(
            action: 'view_report_metrics',
            entity: 'reports',
            metadata: ['tenant_id' => $tenantId]
        );

        $metrics = $this->reportService->getReportMetrics($tenantId);
        $trends = $this->reportService->getReportTrends($tenantId, 30); // Últimos 30 dias

        return $this->renderView('reports.metrics-dashboard', [
            'metrics' => $metrics,
            'trends' => $trends,
            'tenantId' => $tenantId
        ]);
    }

    /**
     * API endpoint para dados de relatórios em tempo real.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function apiReportData(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId();

        if (!$tenantId) {
            return $this->jsonError('Tenant não encontrado.', statusCode: 403);
        }

        $request->validate([
            'type' => 'required|string|in:budget_summary,financial_overview,customer_stats,product_performance',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from'
        ]);

        $data = $this->reportService->getReportDataApi(
            tenantId: $tenantId,
            type: $request->type,
            dateFrom: $request->date_from,
            dateTo: $request->date_to,
            filters: $request->only(['category_id', 'customer_id', 'status'])
        );

        $this->logActivity(
            action: 'api_report_data',
            entity: 'reports',
            metadata: [
                'tenant_id' => $tenantId,
                'report_type' => $request->type,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to
            ]
        );

        return $this->jsonSuccess(
            data: $data,
            message: 'Dados do relatório carregados com sucesso.'
        );
    }

    /**
     * Gera relatório personalizado baseado em critérios definidos.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function customReport(Request $request): View|RedirectResponse
    {
        $tenantId = $this->tenantId();

        if (!$tenantId) {
            return $this->errorRedirect('Tenant não encontrado.');
        }

        $request->validate([
            'entities' => 'required|array',
            'entities.*' => 'in:budgets,services,customers,providers,invoices,products,activities',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'group_by' => 'required|in:date,entity,user,customer,provider,category',
            'format' => 'nullable|in:html,pdf,excel'
        ]);

        $this->logActivity(
            action: 'generate_custom_report',
            entity: 'reports',
            metadata: [
                'tenant_id' => $tenantId,
                'entities' => $request->entities,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'group_by' => $request->group_by,
                'format' => $request->get('format', 'html')
            ]
        );

        $reportData = $this->reportService->generateCustomReport(
            tenantId: $tenantId,
            entities: $request->entities,
            dateFrom: $request->date_from,
            dateTo: $request->date_to,
            groupBy: $request->group_by,
            additionalFilters: $request->only(['status', 'payment_method', 'category_id'])
        );

        if ($request->get('format') === 'pdf') {
            $pdf = PDF::loadView('reports.custom', $reportData);
            $filename = 'relatorio_personalizado_' . date('Y-m-d_H-i-s') . '.pdf';

            return $pdf->download($filename);
        }

        if ($request->get('format') === 'excel') {
            // Implementar exportação Excel personalizada
            $filename = 'relatorio_personalizado_' . date('Y-m-d_H-i-s') . '.xlsx';
            // return Excel::download(new CustomReportExport($reportData), $filename);
            return $this->renderView('reports.custom', $reportData); // Placeholder
        }

        return $this->renderView('reports.custom', $reportData);
    }

    /**
     * Agenda geração de relatório recorrente.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function scheduleReport(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->tenantId();

        if (!$tenantId) {
            if (request()->expectsJson()) {
                return $this->jsonError('Tenant não encontrado.', statusCode: 403);
            }
            return $this->errorRedirect('Tenant não encontrado.');
        }

        $request->validate([
            'report_type' => 'required|string|max:50',
            'frequency' => 'required|in:daily,weekly,monthly',
            'time' => 'required|date_format:H:i',
            'format' => 'required|in:pdf,excel',
            'recipients' => 'required|array',
            'recipients.*' => 'email|max:255',
            'date_from' => 'required|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        $scheduleData = [
            'tenant_id' => $tenantId,
            'report_type' => $request->report_type,
            'frequency' => $request->frequency,
            'time' => $request->time,
            'format' => $request->format,
            'recipients' => $request->recipients,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'is_active' => true,
            'created_by' => $this->userId()
        ];

        $result = $this->reportService->scheduleReport($scheduleData);

        if (request()->expectsJson()) {
            if ($result->isSuccess()) {
                $this->logActivity(
                    action: 'schedule_report',
                    entity: 'reports',
                    entityId: $result->getEntityId(),
                    metadata: [
                        'tenant_id' => $tenantId,
                        'report_type' => $request->report_type,
                        'frequency' => $request->frequency,
                        'recipients_count' => count($request->recipients)
                    ]
                );

                return $this->jsonSuccess(
                    data: $result->getData(),
                    message: 'Relatório agendado com sucesso.'
                );
            }

            return $this->jsonError(
                message: $result->getError() ?? 'Erro ao agendar relatório.',
                statusCode: 422
            );
        }

        return $this->handleServiceResult(
            result: $result,
            request: $request,
            successMessage: 'Relatório agendado com sucesso.',
            errorMessage: 'Erro ao agendar relatório.'
        );
    }

    /**
     * Lista relatórios agendados.
     *
     * @param Request $request
     * @return View
     */
    public function scheduledReports(Request $request): View
    {
        $tenantId = $this->tenantId();

        if (!$tenantId) {
            return $this->errorRedirect('Tenant não encontrado.');
        }

        $filters = [
            'status' => $request->get('status'),
            'type' => $request->get('type'),
            'frequency' => $request->get('frequency')
        ];

        $scheduledReports = $this->reportService->getScheduledReports(
            tenantId: $tenantId,
            filters: $filters
        );

        return $this->renderView('reports.scheduled', [
            'scheduledReports' => $scheduledReports,
            'filters' => $filters,
            'tenantId' => $tenantId,
            'reportTypes' => ['budget' => 'Orçamentos', 'financial' => 'Financeiro', 'customer' => 'Clientes', 'product' => 'Produtos'],
            'frequencies' => ['daily' => 'Diário', 'weekly' => 'Semanal', 'monthly' => 'Mensal'],
            'statuses' => ['active' => 'Ativo', 'inactive' => 'Inativo', 'paused' => 'Pausado']
        ]);
    }
}

