<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\UserConfirmationToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $service = Service::factory()->create(['tenant_id' => $tenant->id]);
        $token = UserConfirmationToken::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        return [
            'tenant_id' => $tenant->id,
            'service_id' => $service->id,
            'user_confirmation_token_id' => $token->id,
            'start_date_time' => $this->faker->dateTimeBetween('+1 day', '+1 week'),
            'end_date_time' => $this->faker->dateTimeBetween('+2 days', '+8 days'),
            'location' => $this->faker->address,
        ];
    }
}
