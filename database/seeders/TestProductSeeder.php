<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Gera produtos de teste usando o mesmo padrão de SKU sequencial (PROD000001, ...)
     * implementado em ProductService::generateUniqueSku(), garantindo consistência
     * com o sistema legado.
     */
    public function run(): void
    {
        $service = app(\App\Services\Domain\ProductService::class);

        // Cria 5 produtos com tenant e SKU gerado pelo service
        \App\Models\Product::factory()
            ->withTenant()
            ->count(5)
            ->create()
            ->each(function (\App\Models\Product $product) use ($service) {
                if (empty($product->sku)) {
                    // Gera SKU sequencial compatível com legado
                    $product->sku = (new \ReflectionClass($service))
                        ->getMethod('generateUniqueSku')
                        ->invoke($service);
                    $product->save();
                }
            });
    }
}
