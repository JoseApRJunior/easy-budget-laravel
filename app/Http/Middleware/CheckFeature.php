<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CheckFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        // Verifica se a feature está ativa via Gate (substituindo Pennant)
        if (Gate::denies($feature)) {
            abort(404, 'Módulo em desenvolvimento ou não disponível.');
        }

        return $next($request);
    }
}
