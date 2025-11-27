<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware para otimizar carregamento de usuário autenticado
 * Carrega relacionamentos necessários de uma vez para evitar N+1 queries
 */
class OptimizeAuthUser
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Eager load de relacionamentos que serão usados frequentemente
            // Tenant já está no $with do model
            if (!$user->relationLoaded('roles')) {
                $user->load([
                    'roles' => function ($query) use ($user) {
                        $query->wherePivot('tenant_id', $user->tenant_id);
                    }
                ]);
            }
        }

        return $next($request);
    }
}
