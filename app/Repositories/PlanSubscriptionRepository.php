<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Tenant\PlanSubscriptionDTO;
use App\Models\PlanSubscription;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Model;

class PlanSubscriptionRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new PlanSubscription;
    }

    /**
     * Cria uma nova assinatura de plano a partir de um DTO.
     */
    public function createFromDTO(PlanSubscriptionDTO $dto): PlanSubscription
    {
        return $this->model->newQuery()->create($dto->toArray());
    }

    /**
     * Atualiza uma assinatura de plano a partir de um DTO.
     */
    public function updateFromDTO(int $id, PlanSubscriptionDTO $dto): bool
    {
        $subscription = $this->find($id);
        if (! $subscription) {
            return false;
        }

        return $subscription->update(array_filter($dto->toArray(), fn ($value) => $value !== null));
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
