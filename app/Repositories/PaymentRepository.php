<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Payment;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositório de Pagamentos.
 */
class PaymentRepository extends AbstractTenantRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    /**
     * Busca pagamentos filtrados por tenant.
     */
    public function getFilteredPayments(int $tenantId, array $filters = []): Collection
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->with(['invoice', 'customer']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->get();
    }
}
