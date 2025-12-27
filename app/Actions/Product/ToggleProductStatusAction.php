<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class ToggleProductStatusAction
{
    public function __construct(
        private ProductRepository $repository
    ) {}

    public function execute(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            if (! $this->repository->canBeDeactivatedOrDeleted($product->id)) {
                throw new Exception('Produto não pode ser desativado/ativado pois está em uso em serviços.');
            }

            $newStatus = ! $product->active;
            $this->repository->updateStatus($product->id, $newStatus);

            return $product->fresh();
        });
    }
}
