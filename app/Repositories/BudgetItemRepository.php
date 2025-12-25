<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BudgetItem;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;
use App\DTOs\Budget\BudgetItemDTO;

class BudgetItemRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new BudgetItem();
    }

    /**
     * Cria um item de orçamento a partir de um DTO.
     */
    public function createFromDTO(BudgetItemDTO $dto, int $budgetId): Model
    {
        $data = $dto->toArray();
        $data['budget_id'] = $budgetId;
        
        return $this->create($data);
    }

    /**
     * Deleta todos os itens de um orçamento.
     */
    public function deleteByBudgetId(int $budgetId): void
    {
        $this->model->where('budget_id', $budgetId)->delete();
    }
}
