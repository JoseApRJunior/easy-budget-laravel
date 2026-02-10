<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
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
        // Verifica se a feature está ativa via Pennant
        // Como as features são registradas dinamicamente no AppServiceProvider,
        // o Pennant cuidará da lógica e do cache.
        if (Feature::inactive($feature)) {
            abort(404, 'Módulo em desenvolvimento ou não disponível.');
        }

        return $next($request);
    }
}
