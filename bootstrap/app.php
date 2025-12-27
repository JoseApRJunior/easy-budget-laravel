<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\MonitoringMiddleware;
use App\Http\Middleware\OptimizeAuthUser;
use App\Http\Middleware\ProviderMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        App\Providers\AuthServiceProvider::class,
        App\Providers\OptimizedAuthServiceProvider::class,
        App\Providers\AliasServiceProvider::class,
        App\Providers\ViewComposerServiceProvider::class,
        App\Providers\BladeDirectiveServiceProvider::class,
        App\Providers\BackupServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'provider' => ProviderMiddleware::class,
            'admin' => AdminMiddleware::class,
            'monitoring' => MonitoringMiddleware::class,
            'optimize.auth' => OptimizeAuthUser::class,
        ]);

        // Adicionar middleware de otimização ao grupo web
        $middleware->web(append: [
            \App\Http\Middleware\OptimizeAuthUser::class,
        ]);

        // Trust Cloudflare proxies for correct URL generation
        $middleware->trustProxies(
            '*', // Trust all proxies (for Cloudflare)
            30 // Trust X-Forwarded-* headers
        );

    })
    ->withExceptions(function (): void {
        // Only attempt to resolve the 'view' instance if the container has it bound.
        // This prevents fatal "Target class [view] does not exist" errors when
        // an exception occurs very early in the application lifecycle.
        if (app()->bound('view')) {
            app('view');
        }
    })->create();
