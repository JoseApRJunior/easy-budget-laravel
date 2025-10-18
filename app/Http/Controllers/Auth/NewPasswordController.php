<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create( Request $request ): View
    {
        Log::info( 'NewPasswordController: Acessando página de criação de nova senha', [
            'token'      => $request->token ? 'present' : 'missing',
            'email'      => $request->email,
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp'  => now()->toISOString()
        ] );

        try {
            return view( 'auth.reset-password', [ 'request' => $request ] );
        } catch ( \Throwable $e ) {
            Log::error( 'NewPasswordController: Erro ao carregar view reset-password', [
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
                'token'      => $request->token ? 'present' : 'missing',
                'email'      => $request->email,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent()
            ] );
            throw $e;
        }
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store( Request $request ): RedirectResponse
    {
        Log::info( 'NewPasswordController: Iniciando processo de criação de nova senha', [
            'email'      => $request->email,
            'token'      => $request->token ? 'present' : 'missing',
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp'  => now()->toISOString()
        ] );

        try {
            $request->validate( [
                'token'    => [ 'required' ],
                'email'    => [ 'required', 'email' ],
                'password' => [ 'required', 'confirmed', Rules\Password::defaults() ],
            ] );

            // Here we will attempt to reset the user's password. If it is successful we
            // will update the password on an actual user model and persist it to the
            // database. Otherwise we will parse the error and return the response.
            $status = Password::reset(
                $request->only( 'email', 'password', 'password_confirmation', 'token' ),
                function ( User $user ) use ( $request ) {
                    Log::info( 'NewPasswordController: Reset de senha realizado com sucesso', [
                        'user_id'   => $user->id,
                        'email'     => $user->email,
                        'timestamp' => now()->toISOString()
                    ] );

                    $user->forceFill( [
                        'password'       => Hash::make( $request->password ),
                        'remember_token' => Str::random( 60 ),
                    ] )->save();

                    event( new PasswordReset( $user ) );
                }
            );

            Log::info( 'NewPasswordController: Status do reset de senha', [
                'email'       => $request->email,
                'status'      => $status,
                'status_code' => $status === Password::PASSWORD_RESET ? 'success' : 'error',
                'timestamp'   => now()->toISOString()
            ] );

            // If the password was successfully reset, we will redirect the user back to
            // the application's home authenticated view. If there is an error we can
            // redirect them back to where they came from with their error message.
            return $status == Password::PASSWORD_RESET
                ? redirect()->route( 'login' )->with( 'status', __( $status ) )
                : back()->withInput( $request->only( 'email' ) )
                    ->withErrors( [ 'email' => __( $status ) ] );
        } catch ( \Throwable $e ) {
            Log::error( 'NewPasswordController: Erro no processo de criação de nova senha', [
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
