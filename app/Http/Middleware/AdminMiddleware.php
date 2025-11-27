<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Check if user has admin role
        if (! $user->hasRole('admin')) {
            Log::warning('Admin access denied', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'attempted_route' => $request->route()->getName(),
            ]);

            abort(403, 'Acesso negado. Você não tem permissões administrativas.');
        }

        return $next($request);
    }
}
