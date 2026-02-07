<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ServiceStatus;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $budget = Budget::factory()->create(['tenant_id' => $tenant->id]);
        $category = Category::factory()->create();

        return [
            'tenant_id' => $tenant->id,
            'budget_id' => $budget->id,
            'category_id' => $category->id,
            'code' => $this->faker->unique()->numerify('SRV-####'),
            'description' => $this->faker->sentence,
            'status' => ServiceStatus::SCHEDULED->value,
            'discount' => 0.0,
            'total' => $this->faker->randomFloat(2, 100, 1000),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
        ];
    }
}
