<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\MonitoringMiddleware;
use App\Http\Middleware\OptimizeAuthUser;
use App\Http\Middleware\ProviderMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'provider' => ProviderMiddleware::class,
            'admin' => AdminMiddleware::class,
            'monitoring' => MonitoringMiddleware::class,
            'optimize.auth' => OptimizeAuthUser::class,
        ]);

        $middleware->web(append: [
            OptimizeAuthUser::class,
        ]);

        $middleware->trustProxies('*', 30);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // 1. Reportar o erro (Lógica de Log)
        $exceptions->report(function (\Throwable $e) {
            try {
                Log::error('Exceção Global:', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => request()->fullUrl(),
                    'user_id' => auth()->id() ?? 'Convidado',
                ]);
            } catch (\Throwable $logError) {
                error_log('Falha crítica no log: '.$logError->getMessage());
            }
        });

        // 2. Renderizar a resposta (Lógica de UI)
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->expectsJson() &&
                ! $e instanceof \Illuminate\Validation\ValidationException &&
                ! $e instanceof \Illuminate\Auth\Access\AuthorizationException &&
                ! $e instanceof \Illuminate\Auth\AuthenticationException &&
                ! $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {

                if ($request->isMethod('GET')) {
                    return response()->view('errors.500', ['exception' => $e], 500);
                }

                return back()->withInput()->with('error', 'Ops! Ocorreu um erro inesperado. Nossa equipe já foi notificada.');
            }
        });
    })->create();
