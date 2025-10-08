<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view( 'auth.login' );
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store( LoginRequest $request ): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Define sessão 'auth' para compatibilidade com sistema existente
        $this->createCustomSession( $request );

        return redirect()->intended( route( 'provider.index', absolute: false ) );
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy( Request $request ): RedirectResponse
    {
        Auth::guard( 'web' )->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect( '/' );
    }

    // app/Http/Controllers/Auth/AuthenticatedSessionController.php
    private function createCustomSession( Request $request ): void
    {
        $user = Auth::user();

        session( [
            'auth' => [
                'id'       => $user->id,
                'name'     => $user->name ?? $user->first_name . ' ' . $user->last_name,
                'email'    => $user->email,
                'role'     => $user->role ?? 'provider',
                'is_admin' => $user->role === 'admin' || $user->role === 'super_admin'
            ]
        ] );
    }

}
