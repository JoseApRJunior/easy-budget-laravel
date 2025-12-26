<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
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
            'tenancy' => \Stancl\Tenancy\Middleware\InitializeTenancyByPath::class,
            'tenancy.prevent' => \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
            'provider' => \App\Http\Middleware\ProviderMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'monitoring' => \App\Http\Middleware\MonitoringMiddleware::class,
            'optimize.auth' => \App\Http\Middleware\OptimizeAuthUser::class,
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
    ->withExceptions(function (Exceptions $exceptions): void {
        // Explicitly resolve the 'view' instance to ensure the ViewServiceProvider is booted.
        // This helps prevent "A facade root has not been set" errors when rendering error views
        // very early in the application lifecycle.
        app('view');
    })->create();
