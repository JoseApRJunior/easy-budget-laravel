<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'Recurso não encontrado'], 404);
            }

            return response()->view('errors.404', [], 404);
        });

        $this->renderable(function (AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'Acesso negado'], 403);
            }

            return response()->view('errors.403', [], 403);
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($this->isHttpException($e)) {
                $statusCode = $e->getStatusCode();

                if ($request->is('api/*')) {
                    return response()->json(['error' => 'Erro interno do servidor'], $statusCode);
                }

                return response()->view('errors.500', [], $statusCode);
            }

            if ($request->is('api/*')) {
                return response()->json(['error' => 'Erro interno do servidor'], 500);
            }

            return response()->view('errors.500', [], 500);
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Página 404 customizada
        if ($exception instanceof NotFoundHttpException) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'Recurso não encontrado'], 404);
            }

            return response()->view('errors.404', [], 404);
        }

        // Página 403 customizada
        if ($exception instanceof AuthorizationException) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'Acesso negado'], 403);
            }

            return response()->view('errors.403', [], 403);
        }

        // Página 500 customizada
        if ($this->isHttpException($exception)) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'Erro interno do servidor'], $exception->getStatusCode());
            }

            return response()->view('errors.500', [], $exception->getStatusCode());
        }

        return parent::render($request, $exception);
    }
}
