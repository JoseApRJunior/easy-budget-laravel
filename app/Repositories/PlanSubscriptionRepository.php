<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PlanSubscription;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;

class PlanSubscriptionRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new PlanSubscription();
    }

    /**
     * Cria uma assinatura.
     */
    public function create(array $data): PlanSubscription
    {
        return $this->model->create($data);
    }

    /**
     * Busca assinatura ativa do provedor.
     */
    public function findActiveByProvider(int $providerId): ?PlanSubscription
    {
        return $this->model->newQuery()
            ->where('provider_id', $providerId)
            ->where('status', PlanSubscription::STATUS_ACTIVE)
            ->where('end_date', '>', now())
            ->first();
    }
}
