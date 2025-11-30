<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Budget;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Service para relatórios financeiros e dashboards.
 */
class FinancialReportService extends AbstractBaseService
{
    /**
     * Retorna dados consolidados para o dashboard financeiro.
     */
    public function getFinancialDashboard(int $tenantId, array $filters = []): ServiceResult
    {
        try {
            $period = $filters['period'] ?? 'current_month';
            $dateRange = $this->getDateRange($period);

            $data = [
                'revenue' => $this->getRevenueData($tenantId, $dateRange),
                'invoices' => $this->getInvoicesData($tenantId, $dateRange),
                'payments' => $this->getPaymentsData($tenantId, $dateRange),
                'budgets' => $this->getBudgetsData($tenantId, $dateRange),
                'charts' => $this->getChartsData($tenantId, $dateRange),
                'period' => $period,
                'date_range' => $dateRange,
            ];

            return $this->success($data, 'Dashboard financeiro carregado');
        } catch (Exception $e) {
            return $this->error(
                \App\Enums\OperationStatus::ERROR,
                'Erro ao carregar dashboard financeiro',
                null,
                $e
            );
        }
    }

    /**
     * Dados de receita.
     */
    private function getRevenueData(int $tenantId, array $dateRange): array
    {
        $currentPeriod = Payment::where('tenant_id', $tenantId)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereBetween('confirmed_at', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');

        $previousPeriod = Payment::where('tenant_id', $tenantId)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereBetween('confirmed_at', [$dateRange['previous_start'], $dateRange['previous_end']])
            ->sum('amount');

        $growth = $previousPeriod > 0 
            ? (($currentPeriod - $previousPeriod) / $previousPeriod) * 100 
            : 0;

        return [
            'current' => (float) $currentPeriod,
            'previous' => (float) $previousPeriod,
            'growth' => round($growth, 1),
            'growth_positive' => $growth >= 0,
        ];
    }

    /**
     * Dados de faturas.
     */
    private function getInvoicesData(int $tenantId, array $dateRange): array
    {
        $total = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $paid = Invoice::where('tenant_id', $tenantId)
            ->where('status', InvoiceStatus::PAID)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $pending = Invoice::where('tenant_id', $tenantId)
            ->where('status', InvoiceStatus::PENDING)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $overdue = Invoice::where('tenant_id', $tenantId)
            ->where('status', InvoiceStatus::OVERDUE)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $totalAmount = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('total');

        $pendingAmount = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', [InvoiceStatus::PENDING, InvoiceStatus::OVERDUE])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('total');

        return [
            'total' => $total,
            'paid' => $paid,
            'pending' => $pending,
            'overdue' => $overdue,
            'total_amount' => (float) $totalAmount,
            'pending_amount' => (float) $pendingAmount,
            'conversion_rate' => $total > 0 ? round(($paid / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Dados de pagamentos.
     */
    private function getPaymentsData(int $tenantId, array $dateRange): array
    {
        $byMethod = Payment::where('tenant_id', $tenantId)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereBetween('confirmed_at', [$dateRange['start'], $dateRange['end']])
            ->select('method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->get()
            ->keyBy('method')
            ->toArray();

        $avgTicket = Payment::where('tenant_id', $tenantId)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereBetween('confirmed_at', [$dateRange['start'], $dateRange['end']])
            ->avg('amount');

        return [
            'by_method' => $byMethod,
            'average_ticket' => (float) $avgTicket,
        ];
    }

    /**
     * Dados de orçamentos.
     */
    private function getBudgetsData(int $tenantId, array $dateRange): array
    {
        $total = Budget::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $approved = Budget::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $totalValue = Budget::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('total');

        return [
            'total' => $total,
            'approved' => $approved,
            'total_value' => (float) $totalValue,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Dados para gráficos.
     */
    private function getChartsData(int $tenantId, array $dateRange): array
    {
        // Receita por dia nos últimos 30 dias
        $revenueChart = Payment::where('tenant_id', $tenantId)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereBetween('confirmed_at', [now()->subDays(30), now()])
            ->select(
                DB::raw('DATE(confirmed_at) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        // Status de faturas (pizza)
        $invoiceStatusChart = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status->getDescription() => $item->count];
            })
            ->toArray();

        // Métodos de pagamento (pizza)
        $paymentMethodChart = Payment::where('tenant_id', $tenantId)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereBetween('confirmed_at', [$dateRange['start'], $dateRange['end']])
            ->select('method', DB::raw('COUNT(*) as count'))
            ->groupBy('method')
            ->get()
            ->mapWithKeys(function ($item) {
                $methods = Payment::getPaymentMethods();
                return [$methods[$item->method] ?? $item->method => $item->count];
            })
            ->toArray();

        return [
            'revenue_timeline' => $revenueChart,
            'invoice_status' => $invoiceStatusChart,
            'payment_methods' => $paymentMethodChart,
        ];
    }

    /**
     * Calcula range de datas baseado no período.
     */
    private function getDateRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'previous_start' => $now->copy()->subDay()->startOfDay(),
                'previous_end' => $now->copy()->subDay()->endOfDay(),
            ],
            'yesterday' => [
                'start' => $now->copy()->subDay()->startOfDay(),
                'end' => $now->copy()->subDay()->endOfDay(),
                'previous_start' => $now->copy()->subDays(2)->startOfDay(),
                'previous_end' => $now->copy()->subDays(2)->endOfDay(),
            ],
            'current_week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'previous_start' => $now->copy()->subWeek()->startOfWeek(),
                'previous_end' => $now->copy()->subWeek()->endOfWeek(),
            ],
            'current_month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'previous_start' => $now->copy()->subMonth()->startOfMonth(),
                'previous_end' => $now->copy()->subMonth()->endOfMonth(),
            ],
            'current_year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'previous_start' => $now->copy()->subYear()->startOfYear(),
                'previous_end' => $now->copy()->subYear()->endOfYear(),
            ],
            'last_30_days' => [
                'start' => $now->copy()->subDays(30),
                'end' => $now->copy(),
                'previous_start' => $now->copy()->subDays(60),
                'previous_end' => $now->copy()->subDays(30),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'previous_start' => $now->copy()->subMonth()->startOfMonth(),
                'previous_end' => $now->copy()->subMonth()->endOfMonth(),
            ],
        };
    }

    /**
     * Gera relatório de vendas detalhado.
     */
    public function getSalesReport(int $tenantId, array $filters = []): ServiceResult
    {
        try {
            $query = Invoice::where('tenant_id', $tenantId)
                ->with(['customer.commonData', 'service.category', 'payments']);

            // Aplicar filtros
            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            if (!empty($filters['customer_id'])) {
                $query->where('customer_id', $filters['customer_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $invoices = $query->orderBy('created_at', 'desc')->get();

            $summary = [
                'total_invoices' => $invoices->count(),
                'total_amount' => $invoices->sum('total'),
                'paid_amount' => $invoices->where('status', InvoiceStatus::PAID)->sum('total'),
                'pending_amount' => $invoices->whereIn('status', [InvoiceStatus::PENDING, InvoiceStatus::OVERDUE])->sum('total'),
            ];

            return $this->success([
                'invoices' => $invoices,
                'summary' => $summary,
                'filters' => $filters,
            ], 'Relatório de vendas gerado');
        } catch (Exception $e) {
            return $this->error(
                \App\Enums\OperationStatus::ERROR,
                'Erro ao gerar relatório de vendas',
                null,
                $e
            );
        }
    }
}