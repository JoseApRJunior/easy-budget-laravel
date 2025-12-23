<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Auth\CachedEloquentUserProvider;

/**
 * Provider para otimizar o sistema de autenticação
 * Adiciona cache no UserProvider para evitar queries duplicadas
 */
class OptimizedAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Extender o Auth para usar um UserProvider com cache
        Auth::provider('cached-eloquent', function ($app, array $config) {
            return new CachedEloquentUserProvider(
                $app['hash'],
                $config['model']
            );
        });
    }
}
