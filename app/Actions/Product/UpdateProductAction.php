<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\DTOs\Product\ProductDTO;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class UpdateProductAction
{
    public function __construct(
        private ProductRepository $repository
    ) {}

    /**
     * Executa a atualização do produto.
     * 
     * @param Product $product
     * @param ProductDTO $dto
     * @return Product
     */
    public function execute(Product $product, ProductDTO $dto): Product
    {
        return DB::transaction(function () use ($product, $dto) {
            $this->repository->updateFromDTO($product->id, $dto);
            return $product->fresh();
        });
    }
}
