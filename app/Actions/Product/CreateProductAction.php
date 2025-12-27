<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\DTOs\Product\ProductDTO;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Repositories\ProductInventoryRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class CreateProductAction
{
    public function __construct(
        private ProductRepository $repository,
        private ProductInventoryRepository $inventoryRepository
    ) {}

    /**
     * Executa a criação do produto.
     * 
     * @param ProductDTO $dto
     * @param UploadedFile|null $image
     * @return Product
     */
    public function execute(ProductDTO $dto, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($dto, $image) {
            // 1. Criar produto base via DTO
            $product = $this->repository->createFromDTO($dto);

            // 2. Inicializar Inventário
            $this->inventoryRepository->initialize($product->id);

            return $product;
        });
    }
}
