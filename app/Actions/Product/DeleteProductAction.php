<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class DeleteProductAction
{
    public function __construct(
        private ProductRepository $repository
    ) {}

    public function execute(Product $product): void
    {
        DB::transaction(function () use ($product) {
            if (! $this->repository->canBeDeactivatedOrDeleted($product->id)) {
                throw new Exception('Produto não pode ser excluído pois está em uso em serviços.');
            }

            $this->repository->delete($product->id);
        });
    }
}
