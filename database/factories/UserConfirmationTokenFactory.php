<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserConfirmationToken>
 */
class UserConfirmationTokenFactory extends Factory
{
    protected $model = UserConfirmationToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'tenant_id'  => Tenant::factory(),
            'token'      => Str::random( 64 ),
            'expires_at' => now()->addMinutes( 30 ),
            'type'       => 'email_verification',
            'metadata'   => null,
        ];
    }

    /**
     * Create a token for social account linking.
     */
    public function socialLinking(): static
    {
        return $this->state( fn( array $attributes ) => [
            'type'     => 'social_linking',
            'metadata' => json_encode( [
                'provider'      => 'google',
                'social_id'     => 'google-user-123',
                'social_name'   => 'João Silva',
                'social_email'  => 'joao.silva@gmail.com',
                'social_avatar' => 'https://avatar.url',
                'user_data'     => [
                    'id'     => 'google-user-123',
                    'name'   => 'João Silva',
                    'email'  => 'joao.silva@gmail.com',
                    'avatar' => 'https://avatar.url',
                ],
            ] ),
        ] );
    }

    /**
     * Create an expired token.
     */
    public function expired(): static
    {
        return $this->state( fn( array $attributes ) => [
            'expires_at' => now()->subMinutes( 5 ),
        ] );
    }

    /**
     * Create a token for a specific user.
     */
    public function forUser( User $user ): static
    {
        return $this->state( fn( array $attributes ) => [
            'user_id'   => $user->id,
            'tenant_id' => $user->tenant_id,
        ] );
    }

    /**
     * Create a token for a specific tenant.
     */
    public function forTenant( Tenant $tenant ): static
    {
        return $this->state( fn( array $attributes ) => [
            'tenant_id' => $tenant->id,
        ] );
    }

}
