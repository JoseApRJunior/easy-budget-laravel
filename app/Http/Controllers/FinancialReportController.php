<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Domain\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para relatórios financeiros.
 */
class FinancialReportController extends Controller
{
    public function __construct(
        private FinancialReportService $financialReportService
    ) {}

    /**
     * Dashboard financeiro principal.
     */
    public function dashboard(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;
        $filters = $request->only(['period']);

        $result = $this->financialReportService->getFinancialDashboard($tenantId, $filters);

        if (! $result->isSuccess()) {
            abort(500, 'Erro ao carregar dashboard financeiro');
        }

        $data = $result->getData();

        return view('pages.financial.dashboard', [
            'revenue' => $data['revenue'],
            'invoices' => $data['invoices'],
            'payments' => $data['payments'],
            'budgets' => $data['budgets'],
            'charts' => $data['charts'],
            'period' => $data['period'],
            'date_range' => $data['date_range'],
            'periods' => [
                'today' => 'Hoje',
                'yesterday' => 'Ontem',
                'current_week' => 'Esta Semana',
                'current_month' => 'Este Mês',
                'current_year' => 'Este Ano',
                'last_30_days' => 'Últimos 30 Dias',
            ],
        ]);
    }

    /**
     * Relatório de vendas.
     */
    public function sales(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;
        $filters = $request->only(['date_from', 'date_to', 'customer_id', 'status']);

        $result = $this->financialReportService->getSalesReport($tenantId, $filters);

        if (! $result->isSuccess()) {
            abort(500, 'Erro ao gerar relatório de vendas');
        }

        $data = $result->getData();

        return view('pages.financial.sales', [
            'invoices' => $data['invoices'],
            'summary' => $data['summary'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * API para dados do dashboard (AJAX).
     */
    public function dashboardData(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $filters = $request->only(['period']);

        $result = $this->financialReportService->getFinancialDashboard($tenantId, $filters);

        return response()->json([
            'success' => $result->isSuccess(),
            'data' => $result->getData(),
            'message' => $result->getMessage(),
        ]);
    }
}
