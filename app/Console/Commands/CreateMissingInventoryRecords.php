<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Console\Command;

class CreateMissingInventoryRecords extends Command
{
    protected $signature = 'inventory:create-missing';

    protected $description = 'Cria registros de inventário para produtos que não possuem';

    public function handle()
    {
        $this->info('Buscando produtos sem inventário...');

        $productsWithoutInventory = Product::query()
            ->whereDoesntHave('inventory')
            ->get();

        if ($productsWithoutInventory->isEmpty()) {
            $this->info('✅ Todos os produtos já possuem inventário!');

            return 0;
        }

        $this->info("Encontrados {$productsWithoutInventory->count()} produtos sem inventário.");
        $bar = $this->output->createProgressBar($productsWithoutInventory->count());
        $bar->start();

        foreach ($productsWithoutInventory as $product) {
            ProductInventory::create([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
                'quantity' => 0,
                'min_quantity' => 0,
                'max_quantity' => null,
            ]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Criados {$productsWithoutInventory->count()} registros de inventário!");

        return 0;
    }
}
