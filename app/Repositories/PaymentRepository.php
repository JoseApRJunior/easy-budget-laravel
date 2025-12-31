<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Payment\PaymentDTO;
use App\Models\Payment;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório de Pagamentos.
 */
class PaymentRepository extends AbstractTenantRepository implements PaymentRepositoryInterface
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Payment;
    }

    /**
     * Busca pagamentos filtrados com paginação.
     */
    public function getFilteredPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['invoice', 'customer'])
            ->tap(fn ($q) => $this->applyAllPaymentFilters($q, $filters))
            ->latest()
            ->paginate($this->getEffectivePerPage($filters, $perPage));
    }

    /**
     * Implementação do método da interface.
     */
    public function getFilteredPayments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->getFilteredPaginated($filters, $perPage);
    }

    /**
     * Aplica todos os filtros de pagamento.
     */
    protected function applyAllPaymentFilters(Builder $query, array $filters): void
    {
        $this->applyBooleanFilter($query, $filters, 'status', 'status');
        $this->applyBooleanFilter($query, $filters, 'method', 'method');
        $this->applyBooleanFilter($query, $filters, 'customer_id', 'customer_id');

        $this->applyDateRangeFilter($query, $filters, 'created_at', 'date_from', 'date_to');
        $this->applyDateRangeFilter($query, $filters, 'created_at', 'start_date', 'end_date');
    }

    /**
     * Obtém estatísticas de pagamentos.
     */
    public function getStats(): array
    {
        $baseQuery = $this->model->newQuery();

        $total = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)->where('status', \App\Enums\PaymentStatus::COMPLETED)->count();
        $pending = (clone $baseQuery)->where('status', \App\Enums\PaymentStatus::PENDING)->count();
        $failed = (clone $baseQuery)->where('status', \App\Enums\PaymentStatus::FAILED)->count();

        $totalAmount = (float) (clone $baseQuery)
            ->where('status', \App\Enums\PaymentStatus::COMPLETED)
            ->sum('amount');

        return [
            'total_payments' => $total,
            'completed_payments' => $completed,
            'pending_payments' => $pending,
            'failed_payments' => $failed,
            'total_amount' => $totalAmount,
            'success_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Cria um pagamento a partir de um DTO.
     */
    public function createFromDTO(PaymentDTO $dto): Payment
    {
        return $this->create($dto->toDatabaseArray());
    }

    /**
     * Atualiza um pagamento a partir de um DTO.
     */
    public function updateFromDTO(int $id, PaymentDTO $dto): ?Model
    {
        $data = $dto->toDatabaseArray();
        $filteredData = array_filter($data, fn ($value) => $value !== null);

        return $this->update($id, $filteredData);
    }
}
