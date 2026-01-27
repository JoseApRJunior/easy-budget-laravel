<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Helpers\DateHelper;
use App\Http\Controllers\Abstracts\Controller;
use App\Models\Tenant;
use App\Services\Admin\FinancialControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FinancialControlController extends Controller
{
    protected FinancialControlService $financialControlService;

    public function __construct(FinancialControlService $financialControlService)
    {
        $this->financialControlService = $financialControlService;
    }

    public function index(): View
    {
        $financialOverview = $this->financialControlService->getFinancialOverview();
        $budgetAlerts = $this->financialControlService->getBudgetAlerts();

        return view('admin.financial.index', compact('financialOverview', 'budgetAlerts'));
    }

    public function providerDetails(string $tenantId): View
    {
        $providerFinancialDetails = $this->financialControlService->getProviderFinancialDetails($tenantId);

        return view('admin.financial.provider-details', compact('providerFinancialDetails'));
    }

    public function reports(Request $request): View
    {
        $filters = [
            'start_date' => DateHelper::toCarbon($request->get('start_date')),
            'end_date' => DateHelper::toCarbon($request->get('end_date')),
            'tenant_id' => $request->get('tenant_id'),
        ];

        $reports = $this->financialControlService->getFinancialReports($filters);
        $tenants = Tenant::all();

        return view('admin.financial.reports', compact('reports', 'filters', 'tenants'));
    }

    public function budgetAlerts(): JsonResponse
    {
        $budgetAlerts = $this->financialControlService->getBudgetAlerts();

        return response()->json([
            'success' => true,
            'alerts' => $budgetAlerts,
        ]);
    }

    public function exportReports(Request $request): Response
    {
        $filters = [
            'start_date' => DateHelper::toCarbon($request->get('start_date')),
            'end_date' => DateHelper::toCarbon($request->get('end_date')),
            'tenant_id' => $request->get('tenant_id'),
        ];

        $reports = $this->financialControlService->getFinancialReports($filters);

        // Generate CSV content
        $csvContent = $this->generateCsvReport($reports);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="financial_report_'.date('Y-m-d').'.csv"',
        ]);
    }

    private function generateCsvReport(array $reports): string
    {
        $csv = 'Relatório Financeiro - '.date('d/m/Y')."\n\n";

        // Revenue by period
        $csv .= "Receita por Período\n";
        $csv .= "Data;Total\n";
        foreach ($reports['revenue_by_period'] as $revenue) {
            $csv .= $revenue['date'].';'.number_format($revenue['total'], 2, ',', '.')."\n";
        }

        $csv .= "\nCustos por Categoria\n";
        $csv .= "Categoria;Total\n";
        foreach ($reports['costs_by_category'] as $category => $amount) {
            $csv .= ucfirst($category).';'.number_format($amount, 2, ',', '.')."\n";
        }

        $csv .= "\nMétodos de Pagamento\n";
        $csv .= "Método;Quantidade;Total\n";
        foreach ($reports['payment_method_analysis'] as $method) {
            $csv .= $method['payment_method'].';'.$method['count'].';'.number_format($method['total'], 2, ',', '.')."\n";
        }

        return $csv;
    }

    private function getDefaultOverview(): array
    {
        return [
            'total_revenue' => 0,
            'total_costs' => 0,
            'net_profit' => 0,
            'profit_margin' => 0,
            'active_providers' => 0,
            'monthly_growth' => 0,
            'avg_revenue_per_provider' => 0,
        ];
    }

    private function getDefaultProviderDetails(): array
    {
        return [
            'provider_name' => 'N/A',
            'tenant_id' => 0,
            'revenue' => ['total' => 0, 'this_month' => 0, 'last_month' => 0, 'growth_rate' => 0],
            'costs' => ['total' => 0, 'subscription' => 0, 'payment_fees' => 0, 'operational' => 0],
            'profitability' => ['net_profit' => 0, 'profit_margin' => 0],
            'metrics' => ['avg_ticket' => 0, 'customer_lifetime_value' => 0, 'invoice_payment_rate' => 0],
            'alerts' => [],
        ];
    }

    private function getDefaultReports(): array
    {
        return [
            'revenue_by_period' => [],
            'costs_by_category' => [],
            'provider_performance' => [],
            'payment_method_analysis' => [],
            'outstanding_receivables' => [],
            'financial_trends' => [],
        ];
    }
}
