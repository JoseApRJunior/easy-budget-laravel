<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantMiddleware
{
    public function handle( Request $request, Closure $next )
    {
        $user = Auth::user();

        if ( !$user ) {
            return redirect()->route( 'login' );
        }

        // Set tenant context from user
        $tenantId = $user->tenant_id ?? 1;
        $request->merge( [ 'tenant_id' => $tenantId ] );

        // Set tenant in session if needed
        session( [ 'tenant_id' => $tenantId ] );

        return $next( $request );
    }

}
