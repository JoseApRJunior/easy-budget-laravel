<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\UserRegistrationService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private readonly UserRegistrationService $userRegistrationService,
    ) {}

    public function showLoginForm(): View
    {
        return view( 'pages.login.index' );
    }

    public function login( Request $request ): RedirectResponse
    {
        $request->validate( [ 
            'email'    => 'required|email',
            'password' => 'required|string',
        ] );

        $key = 'login:' . $request->ip();

        if ( RateLimiter::tooManyAttempts( $key, 5 ) ) {
            $seconds = RateLimiter::availableIn( $key );

            RateLimiter::hit( $key, 60 );

            throw ValidationException::withMessages( [ 
                'email' => trans( 'auth.throttle', [ 
                    'seconds' => $seconds,
                    'minutes' => ceil( $seconds / 60 ),
                ] ),
            ] );
        }

        $credentials = $request->only( 'email', 'password' );

        if ( Auth::attempt( $credentials, $request->boolean( 'remember' ) ) ) {
            $request->session()->regenerate();
            RateLimiter::clear( $key );

            $user = Auth::user();
            if ( $user->status !== 'active' ) {
                Auth::logout();
                return redirect()->route( 'login' )->with( 'error', 'Conta não ativa. Verifique seu email de confirmação.' );
            }

            return redirect()->intended( route( 'home' ) );
        }

        RateLimiter::hit( $key, 60 );

        throw ValidationException::withMessages( [ 
            'email' => 'Credenciais inválidas.',
        ] );
    }

    public function logout( Request $request ): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route( 'home' );
    }

    public function showForgotPassword(): View
    {
        return view( 'pages.auth.forgot-password' );
    }

    public function forgotPassword( Request $request ): RedirectResponse
    {
        $request->validate( [ 'email' => 'required|email' ] );

        $status = Password::sendResetLink(
            $request->only( 'email' ),
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with( 'status', __( $status ) )
            : back()->withErrors( [ 'email' => __( $status ) ] );
    }

    public function showResetPassword( Request $request ): View
    {
        return view( 'pages.auth.reset-password', [ 'token' => $request->route( 'token' ) ] );
    }

    public function resetPassword( Request $request ): RedirectResponse
    {
        $request->validate( [ 
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ] );

        $status = Password::reset(
            $request->only( 'email', 'password', 'password_confirmation', 'token' ),
            function ($user, $password) {
                $user->forceFill( [ 
                    'password' => Hash::make( $password )
                ] )->setRememberToken( Str::random( 60 ) );

                $user->save();

                event( new PasswordReset( $user ) );
            }
        );

        if ( $status === Password::PASSWORD_RESET ) {
            $this->userRegistrationService->initiatePasswordReset( $request->email ); // Log or additional action

            return redirect()->route( 'login' )->with( 'status', __( $status ) );
        }

        return back()->withErrors( [ 'email' => [ __( $status ) ] ] );
    }

}
