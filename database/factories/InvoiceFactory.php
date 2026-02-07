<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $budget = Budget::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);
        $service = Service::factory()->create([
            'tenant_id' => $tenant->id,
            'budget_id' => $budget->id,
        ]);

        return [
            'tenant_id' => $tenant->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'code' => $this->faker->unique()->numerify('INV-####'),
            'subtotal' => $this->faker->randomFloat(2, 100, 1000),
            'discount' => 0.0,
            'total' => $this->faker->randomFloat(2, 100, 1000),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'payment_method' => 'pix',
            'status' => 'PENDING',
        ];
    }
}
