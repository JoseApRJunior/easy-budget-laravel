<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class RestoreProductAction
{
    public function __construct(
        private ProductRepository $repository
    ) {}

    public function execute(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            return $this->repository->restore($product->id);
        });
    }
}
