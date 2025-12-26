<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProductInventory;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para controle de inventário de produtos.
 */
class ProductInventoryRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    /**
     * {@inheritdoc}
     */
    protected function makeModel(): Model
    {
        return new ProductInventory();
    }

    /**
     * Inicializa o inventário para um novo produto.
     */
    public function initialize(int $productId): Model
    {
        return $this->create([
            'product_id'   => $productId,
            'quantity'     => 0,
            'min_quantity' => 0,
            'max_quantity' => null,
        ]);
    }

    /**
     * Busca inventário por produto.
     */
    public function findByProduct(int $productId): ?Model
    {
        return $this->model->newQuery()
            ->where('product_id', $productId)
            ->first();
    }
}
