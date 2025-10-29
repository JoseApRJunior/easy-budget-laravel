<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Provider>
 */
class ProviderFactory extends Factory
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
            'user_id'        => User::factory(),
            'common_data_id' => CommonData::factory(),
            'contact_id'     => Contact::factory(),
            'address_id'     => Address::factory(),
            'terms_accepted' => $this->faker->boolean( 90 ), // 90% chance de aceitar termos
        ];
    }

}
