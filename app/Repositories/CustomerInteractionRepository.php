<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Customer\CustomerInteractionDTO;
use App\Models\CustomerInteraction;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório para Interações com Clientes.
 */
class CustomerInteractionRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado.
     */
    protected function makeModel(): Model
    {
        return new CustomerInteraction;
    }

    /**
     * Cria uma nova interação a partir de um DTO.
     */
    public function createFromDTO(int $customerId, int $userId, CustomerInteractionDTO $dto): CustomerInteraction
    {
        $data = $dto->toArray();
        $data['customer_id'] = $customerId;
        $data['user_id'] = $userId;
        $data['is_active'] = true;

        /** @var CustomerInteraction */
        return $this->create($data);
    }

    /**
     * Atualiza uma interação a partir de um DTO.
     */
    public function updateFromDTO(int $id, CustomerInteractionDTO $dto): ?CustomerInteraction
    {
        /** @var CustomerInteraction|null */
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    /**
     * Busca interações paginadas de um cliente com filtros.
     */
    public function getPaginatedByCustomer(int $customerId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->with(['user'])
            ->orderBy('interaction_date', 'desc');

        // Aplicar filtros específicos se fornecidos
        if (! empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (! empty($filters['direction'])) {
            $query->ofDirection($filters['direction']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->inDateRange($filters['start_date'], $filters['end_date']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['pending_actions'])) {
            $query->pendingActions();
        }

        return $query->paginate($this->getEffectivePerPage($filters, 15));
    }

    /**
     * Obtém timeline de interações para um usuário.
     */
    public function getTimeline(int $userId, int $days = 30, int $limit = 50)
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('interaction_date', '>=', now()->subDays($days))
            ->with(['customer'])
            ->orderBy('interaction_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém próximas ações pendentes para um usuário.
     */
    public function getPendingActions(int $userId, int $limit = 20)
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->pendingActions()
            ->with(['customer'])
            ->orderBy('next_action_date', 'asc')
            ->limit($limit)
            ->get();
    }
}
