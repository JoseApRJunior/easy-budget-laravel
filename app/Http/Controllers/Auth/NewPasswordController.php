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

        // ADICIONADO: Log detalhado para diagnóstico do processo de reset
        Log::info( 'NewPasswordController: Dados recebidos para reset de senha', [
            'email'           => $request->email,
            'token_present'   => !empty( $request->token ),
            'token_length'    => strlen( $request->token ?? '' ),
            'password_length' => strlen( $request->password ?? '' ),
            'ip'              => $request->ip(),
            'user_agent'      => $request->userAgent(),
            'timestamp'       => now()->toISOString()
        ] );

        try {
            $request->validate( [
                'token'    => [ 'required' ],
                'email'    => [ 'required', 'email' ],
                'password' => [ 'required', 'confirmed', Rules\Password::defaults() ],
            ] );

            // Usar sistema legado: buscar token na tabela user_confirmation_tokens
            Log::info( 'NewPasswordController: Iniciando validação usando sistema legado', [
                'email'     => $request->email,
                'token'     => substr( $request->token ?? '', 0, 10 ) . '...',
                'timestamp' => now()->toISOString()
            ] );

            // Buscar token na tabela user_confirmation_tokens
            $confirmationToken = \App\Models\UserConfirmationToken::where( 'token', $request->token )
                ->where( 'type', \App\Enums\TokenType::PASSWORD_RESET )
                ->where( 'expires_at', '>', now() )
                ->first();

            if ( !$confirmationToken ) {
                Log::warning( 'NewPasswordController: Token inválido ou expirado', [
                    'email'     => $request->email,
                    'token'     => substr( $request->token ?? '', 0, 10 ) . '...',
                    'timestamp' => now()->toISOString()
                ] );

                return back()->withInput( $request->only( 'email' ) )
                    ->withErrors( [ 'email' => __( 'passwords.token' ) ] );
            }

            // Verificar se o e-mail corresponde
            if ( $confirmationToken->user->email !== $request->email ) {
                Log::warning( 'NewPasswordController: E-mail não corresponde ao token', [
                    'email'       => $request->email,
                    'token_email' => $confirmationToken->user->email,
                    'token_id'    => $confirmationToken->id,
                    'timestamp'   => now()->toISOString()
                ] );

                return back()->withInput( $request->only( 'email' ) )
                    ->withErrors( [ 'email' => __( 'passwords.token' ) ] );
            }

            // Atualizar senha do usuário
            $user = $confirmationToken->user;
            $user->forceFill( [
                'password'       => Hash::make( $request->password ),
                'remember_token' => Str::random( 60 ),
            ] )->save();

            // Remover token usado
            $confirmationToken->delete();

            Log::info( 'NewPasswordController: Reset de senha realizado com sucesso usando sistema legado', [
                'user_id'   => $user->id,
                'email'     => $user->email,
                'token_id'  => $confirmationToken->id,
                'timestamp' => now()->toISOString()
            ] );

            event( new PasswordReset( $user ) );

            $status = Password::PASSWORD_RESET;

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
