<?php

namespace Database\Factories;

use App\Models\UserConfirmationToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserConfirmationTokenFactory extends Factory
{
    protected $model = UserConfirmationToken::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'tenant_id' => \App\Models\Tenant::factory(),
            'token' => $this->generateBase64UrlToken(), // Gera token no formato base64url
            'expires_at' => now()->addMinutes(30),
        ];
    }

    /**
     * Gera um token no formato base64url (32 bytes = 43 caracteres, formato seguro para URLs).
     */
    private function generateBase64UrlToken(): string
    {
        // Gera 32 bytes aleatórios (256 bits de entropia)
        $randomBytes = random_bytes(32);

        // Converte para base64 e depois para base64url
        $base64 = base64_encode($randomBytes);

        // Substitui caracteres específicos para formato base64url
        $base64url = strtr($base64, '+/', '-_');

        // Remove padding se necessário para ficar exatamente 43 caracteres
        return rtrim($base64url, '=');
    }

    /**
     * Indica que o token está expirado.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * Indica que o token não está expirado.
     */
    public function notExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addMinutes(30),
        ]);
    }
}
