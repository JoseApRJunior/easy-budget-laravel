<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Provider;

class ProviderMiddleware
{
    public function handle( Request $request, Closure $next )
    {
        $user = Auth::user();

        if ( !$user ) {
            return redirect()->route( 'login' );
        }

        // Check if user is a provider
        if ( !$user->role || $user->role->name !== 'provider' ) {
            Log::warning( 'Provider access denied', [ 
                'user_id'         => $user->id,
                'ip'              => $request->ip(),
                'attempted_route' => $request->route()->getName()
            ] );

            abort( 403, 'Acesso negado. Você não tem permissões de provedor.' );
        }

        // Check if provider is active
        $provider = Provider::where( 'user_id', $user->id )->first();
        if ( !$provider || $provider->status !== 'active' ) {
            abort( 403, 'Acesso negado. Sua conta de provedor não está ativa.' );
        }

        return $next( $request );
    }

}