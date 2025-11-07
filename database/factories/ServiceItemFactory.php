<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceItemFactory extends Factory
{
    protected $model = ServiceItem::class;

    public function definition(): array
    {
        $tenant  = Tenant::factory()->create();
        $service = Service::factory()->create( [ 'tenant_id' => $tenant->id ] );
        $product = Product::factory()->create( [ 'tenant_id' => $tenant->id ] );

        return [
            'tenant_id'  => $tenant->id,
            'service_id' => $service->id,
            'product_id' => $product->id,
            'unit_value' => $this->faker->randomFloat( 2, 10, 100 ),
            'quantity'   => $this->faker->numberBetween( 1, 10 ),
            'total'      => $this->faker->randomFloat( 2, 50, 1000 ),
        ];
    }

}
