<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Invoice;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório para gerenciamento de faturas.
 *
 * Estende AbstractTenantRepository para operações tenant-aware
 * com isolamento automático de dados por empresa.
 */
class InvoiceRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Invoice();
    }

    /**
     * Busca uma fatura por código com relações opcionais.
     */
    public function findByCode(string $code, array $with = []): ?Invoice
    {
        $query = $this->model->newQuery()->where('code', $code);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->first();
    }

    /**
     * Verifica se a fatura possui pagamentos.
     */
    public function hasPayments(int $invoiceId): bool
    {
        return $this->model->newQuery()
            ->where('id', $invoiceId)
            ->whereHas('payments')
            ->exists();
    }

    /**
     * Deleta fatura pelo código.
     */
    public function deleteByCode(string $code): bool
    {
        return (bool) $this->model->newQuery()
            ->where('code', $code)
            ->delete();
    }

    /**
     * Atualiza status pelo código.
     */
    public function updateStatusByCode(string $code, string $status): bool
    {
        return (bool) $this->model->newQuery()
            ->where('code', $code)
            ->update(['status' => $status]);
    }

    /**
     * Busca faturas com filtros e paginação.
     */
    public function getFiltered(array $filters = [], ?array $orderBy = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->tap(fn($q) => $this->applyAllInvoiceFilters($q, $filters))
            ->when(!$orderBy, fn($q) => $q->latest())
            ->when($orderBy, fn($q) => $this->applyOrderBy($q, $orderBy))
            ->paginate($this->getEffectivePerPage($filters, $perPage));
    }

    /**
     * Aplica todos os filtros de fatura de forma segura.
     */
    protected function applyAllInvoiceFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        $this->applySearchFilter($query, $filters, ['code']);
        $this->applyBooleanFilter($query, $filters, 'status', 'status');
        $this->applyBooleanFilter($query, $filters, 'customer_id', 'customer_id');

        if (!empty($filters['date_from'])) {
            $query->whereDate('due_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('due_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->orWhereHas('customer.commonData', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }
    }

    /**
     * Obtém estatísticas de faturas para o dashboard.
     */
    public function getDashboardStats(): array
    {
        $baseQuery = $this->model->newQuery();

        $total     = (clone $baseQuery)->count();
        $paid      = (clone $baseQuery)->where('status', \App\Enums\InvoiceStatus::PAID->value)->count();
        $pending   = (clone $baseQuery)->where('status', \App\Enums\InvoiceStatus::PENDING->value)->count();
        $overdue   = (clone $baseQuery)->where('status', \App\Enums\InvoiceStatus::OVERDUE->value)->count();
        $cancelled = (clone $baseQuery)->where('status', \App\Enums\InvoiceStatus::CANCELLED->value)->count();

        $totalBilled   = (float) (clone $baseQuery)->sum('total');
        $totalReceived = (float) (clone $baseQuery)->where('status', \App\Enums\InvoiceStatus::PAID->value)->sum('transaction_amount');
        $totalPending  = (float) (clone $baseQuery)->whereIn('status', [\App\Enums\InvoiceStatus::PENDING->value, \App\Enums\InvoiceStatus::OVERDUE->value])->sum('total');

        $statusBreakdown = [
            'PENDENTE'  => ['count' => $pending, 'color' => \App\Enums\InvoiceStatus::PENDING->getColor()],
            'VENCIDA'   => ['count' => $overdue, 'color' => \App\Enums\InvoiceStatus::OVERDUE->getColor()],
            'PAGA'      => ['count' => $paid, 'color' => \App\Enums\InvoiceStatus::PAID->getColor()],
            'CANCELADA' => ['count' => $cancelled, 'color' => \App\Enums\InvoiceStatus::CANCELLED->getColor()],
        ];

        $recent = (clone $baseQuery)
            ->latest('created_at')
            ->limit(10)
            ->with(['customer.commonData', 'service'])
            ->get();

        return [
            'total_invoices'     => $total,
            'paid_invoices'      => $paid,
            'pending_invoices'   => $pending,
            'overdue_invoices'   => $overdue,
            'cancelled_invoices' => $cancelled,
            'total_billed'       => $totalBilled,
            'total_received'     => $totalReceived,
            'total_pending'      => $totalPending,
            'status_breakdown'   => $statusBreakdown,
            'recent_invoices'    => $recent,
        ];
    }

    /**
     * Cria uma fatura a partir de um DTO.
     */
    public function createFromDTO(\App\DTOs\Invoice\InvoiceDTO $dto): Model
    {
        return $this->create($dto->toArrayWithoutNulls());
    }

    /**
     * Atualiza uma fatura a partir de um DTO.
     */
    public function updateFromDTO(int $id, \App\DTOs\Invoice\InvoiceUpdateDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    /**
     * Soma o total de faturas vinculadas a um orçamento com determinados status.
     */
    public function sumTotalByBudgetId(int $budgetId, array $statuses): float
    {
        return (float) $this->model->newQuery()->whereHas('service', function ($query) use ($budgetId) {
            $query->where('budget_id', $budgetId);
        })
            ->whereIn('status', $statuses)
            ->sum('total');
    }

    /**
     * Verifica se já existe uma fatura para um determinado serviço.
     */
    public function existsForService(int $serviceId): bool
    {
        return $this->model->newQuery()->where('service_id', $serviceId)->exists();
    }

    /**
     * Pesquisa faturas por código ou nome do cliente.
     */
    public function search(string $query, int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                    ->orWhereHas('customer', function ($cq) use ($query) {
                        $cq->where('name', 'like', "%{$query}%");
                    });
            })
            ->limit($limit)
            ->with('customer')
            ->get(['id', 'code', 'customer_id']);
    }
}
