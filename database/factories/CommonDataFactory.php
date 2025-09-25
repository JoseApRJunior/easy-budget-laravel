<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CommonData;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommonDataFactory extends Factory
{
    protected $model = CommonData::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'key' => $this->faker->word,
            'value' => $this->faker->sentence,
            'description' => $this->faker->text(100),
        ];
    }
}