<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Support\Str;

/**
 * Trait para gerenciar tokens públicos em modelos.
 * Garante que um token público seja gerado automaticamente se não existir.
 */
trait HasPublicToken
{
    /**
     * Boot do trait.
     */
    public static function bootHasPublicToken(): void
    {
        static::creating(function ($model) {
            if (empty($model->public_token)) {
                $model->public_token = self::generateUniquePublicToken();
            }

            if (empty($model->public_expires_at)) {
                $model->public_expires_at = now()->addDays(30);
            }
        });
    }

    /**
     * Gera um token público único.
     */
    public static function generateUniquePublicToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('public_token', $token)->exists());

        return $token;
    }

    /**
     * Renova o token público.
     */
    public function renewPublicToken(int $days = 30): void
    {
        $this->update([
            'public_token' => self::generateUniquePublicToken(),
            'public_expires_at' => now()->addDays($days),
        ]);
    }
}
