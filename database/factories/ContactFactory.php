<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id'      => Tenant::factory(),
            'email'          => $this->faker->unique()->safeEmail,
            'phone'          => $this->faker->phoneNumber,
            'email_business' => $this->faker->unique()->companyEmail,
            'phone_business' => $this->faker->phoneNumber,
            'website'        => $this->faker->url,
        ];
    }

}
