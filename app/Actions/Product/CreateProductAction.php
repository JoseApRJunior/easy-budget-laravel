<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\DTOs\Product\ProductDTO;
use App\Models\Product;
use App\Repositories\ProductInventoryRepository;
use App\Repositories\ProductRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateProductAction
{
    public function __construct(
        private ProductRepository $repository,
        private ProductInventoryRepository $inventoryRepository
    ) {}

    /**
     * Executa a criação do produto.
     */
    public function execute(ProductDTO $dto, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($dto) {
            // 1. Criar produto base via DTO
            $product = $this->repository->createFromDTO($dto);

            // 2. Inicializar Inventário
            $this->inventoryRepository->initialize($product->id);

            return $product;
        });
    }
}
