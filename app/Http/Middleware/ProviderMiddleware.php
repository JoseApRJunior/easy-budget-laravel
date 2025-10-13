<?php

namespace App\Http\Middleware;

use App\Models\Provider;
use App\Models\User;
use App\Models\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProviderMiddleware
{
    public function handle( Request $request, Closure $next )
    {
        $user = Auth::user();

        if ( !$user ) {
            return redirect()->route( 'login' );
        }

        // Check if user is a provider
        $isProvider = UserRole::where( 'user_id', $user->id )
            ->where( 'tenant_id', $user->tenant_id )
            ->whereHas( 'role', function ( $query ) {
                $query->where( 'name', 'provider' );
            } )
            ->exists();

        if ( !$isProvider ) {
            Log::warning( 'Provider access denied', [
                'user_id'         => $user->id,
                'ip'              => $request->ip(),
                'attempted_route' => $request->route()->getName()
            ] );

            abort( 403, 'Acesso negado. Você não tem permissões de provedor.' );
        }

        // Check if provider is active
        $provider = Provider::where( 'user_id', $user->id )->first();
        if ( !$provider || $user->is_active !== true ) {
            abort( 403, 'Acesso negado. Sua conta de usuário não está ativa.' );
        }

        // Check if trial is expired and redirect to plans
        if ( isTrialExpired() ) {
            Log::info( 'Trial expired - redirecting to plans', [
                'user_id' => $user->id,
                'ip'      => $request->ip()
            ] );

            return redirect()->route( 'plans.index' )
                ->with( 'warning', 'Seu período de trial expirou. Escolha um plano para continuar usando o sistema.' );
        }

        return $next( $request );
    }

}
