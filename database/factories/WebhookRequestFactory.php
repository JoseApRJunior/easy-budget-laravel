<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\WebhookRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookRequest>
 */
class WebhookRequestFactory extends Factory
{
    protected $model = WebhookRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'request_id' => $this->faker->uuid(),
            'type' => $this->faker->randomElement(['invoice', 'payment', 'subscription']),
            'payload' => ['test' => 'data'],
            'processed' => false,
            'response' => null,
            'error_message' => null,
            'attempts' => 0,
            'last_attempt_at' => null,
            'processed_at' => null,
        ];
    }
}