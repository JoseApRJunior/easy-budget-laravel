<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Abstracts\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        try {
            return view( 'auth.forgot-password' );
        } catch ( \Throwable $e ) {
            Log::error( 'PasswordResetLinkController: Erro ao carregar view forgot-password', [
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
                'user_agent' => request()->userAgent(),
                'ip'         => request()->ip()
            ] );
            throw $e;
        }
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store( Request $request ): RedirectResponse
    {
        try {
            $request->validate( [
                'email' => [ 'required', 'email' ],
            ] );

            // We will send the password reset link to this user. Once we have attempted
            // to send the link, we will examine the response then see the message we
            // need to show to the user. Finally, we'll send out a proper response.
            $status = Password::sendResetLink(
                $request->only( 'email' ),
            );

            Log::info( 'PasswordResetLinkController: Status do envio de link de reset', [
                'email'       => $request->email,
                'status'      => $status,
                'status_code' => $status === Password::RESET_LINK_SENT ? 'success' : 'error',
                'timestamp'   => now()->toISOString()
            ] );

            return $status == Password::RESET_LINK_SENT
                ? back()->with( 'status', __( $status ) )
                : back()->withInput( $request->only( 'email' ) )
                    ->withErrors( [ 'email' => __( $status ) ] );
        } catch ( \Throwable $e ) {
            Log::error( 'PasswordResetLinkController: Erro no processo de reset de senha', [
                'email'      => $request->email,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent()
            ] );

            return back()->withInput( $request->only( 'email' ) )
                ->withErrors( [ 'email' => 'Erro interno do servidor. Tente novamente mais tarde.' ] );
        }
    }

}
