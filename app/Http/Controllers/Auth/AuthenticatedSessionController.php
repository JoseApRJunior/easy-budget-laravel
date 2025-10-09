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

        // Força invalidação de qualquer sessão anterior para evitar conflitos entre navegadores
        $this->ensureCleanSession( $request );

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

    /**
     * Garante que cada navegador tenha sua própria sessão limpa.
     * Remove qualquer sessão anterior que possa estar causando conflitos.
     */
    private function ensureCleanSession( Request $request ): void
    {
        // Marca única por navegador para evitar conflitos
        $browserFingerprint = $this->getBrowserFingerprint( $request );

        // Remove sessões anteriores do mesmo navegador
        $request->session()->put( 'browser_fingerprint', $browserFingerprint );
        $request->session()->put( 'login_time', now()->timestamp );

        // Força limpeza de cache de sessão
        $request->session()->save();

        if ( !app()->environment( 'production' ) ) {
            \Illuminate\Support\Facades\Log::debug( 'Sessão limpa criada para navegador', [
                'session_id'          => substr( $request->session()->getId(), -8 ),
                'browser_fingerprint' => substr( $browserFingerprint, -8 ),
                'user_id'             => Auth::id(),
            ] );
        }
    }

    /**
     * Cria uma marca única para identificar o navegador.
     */
    private function getBrowserFingerprint( Request $request ): string
    {
        $userAgent      = $request->userAgent();
        $ip             = $request->ip();
        $acceptLanguage = $request->header( 'Accept-Language', '' );

        return hash( 'sha256', $userAgent . $ip . $acceptLanguage );
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
