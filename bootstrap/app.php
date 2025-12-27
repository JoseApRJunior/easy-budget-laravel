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
    ->withExceptions(function (\Illuminate\Foundation\Configuration\Exceptions $exceptions) {
        // Renderização global de exceções para uma experiência de usuário limpa
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Se for uma requisição que espera JSON (API), o Laravel já trata bem,
            // mas podemos customizar se necessário.

            // Para requisições Web, se não for uma exceção de validação ou autorização (que o Laravel já trata)
            if (!$request->expectsJson() &&
                !$e instanceof \Illuminate\Validation\ValidationException &&
                !$e instanceof \Illuminate\Auth\Access\AuthorizationException &&
                !$e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {

                // Logamos o erro detalhadamente uma única vez aqui
                \Illuminate\Support\Facades\Log::error('Exceção Global:', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id(),
                ]);

                // Redirecionamos o usuário de volta com uma mensagem amigável
                return back()->withInput()->with('error', 'Ops! Ocorreu um erro inesperado. Nossa equipe já foi notificada.');
            }
        });

        // Configuração de reporte (opcional)
        $exceptions->report(function (\Throwable $e) {
            // Aqui você poderia integrar com Sentry, Flare, etc.
        });
    })->create();
