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
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'cpf' => $this->faker->numerify('###########'), // CPF brasileiro
            'cnpj' => $this->faker->numerify('##############'), // CNPJ brasileiro
            'company_name' => $this->faker->company,
            'description' => $this->faker->text(200),
        ];
    }
}
