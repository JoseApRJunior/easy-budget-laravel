<?php

namespace App\Http\Controllers\Auth;

use App\Events\PasswordResetRequested;
use App\Http\Controllers\Abstracts\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

use function App\Support\generateSecureToken;

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
            Log::error( 'PasswordResetLinkController::create - Erro ao carregar view forgot-password', [
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
     * Implementa fluxo completo de reset de senha com:
     * - Validação de e-mail
     * - Geração de token de reset em formato base64url (32 bytes = 43 caracteres)
     * - Disparo de evento personalizado PasswordResetRequested
     * - Integração com sistema de e-mail avançado (MailerService)
     * - Logging detalhado para auditoria
     * - Tratamento robusto de erros
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store( Request $request ): RedirectResponse
    {
        try {
            Log::info( 'PasswordResetLinkController::store - Iniciando processo de reset de senha', [
                'email'      => $request->email,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp'  => now()->toISOString()
            ] );

            // 1. Validação do e-mail
            $request->validate( [
                'email' => [ 'required', 'email' ],
            ] );

            // 2. Buscar usuário pelo e-mail
            $user = User::where( 'email', $request->email )->first();

            Log::info( 'PasswordResetLinkController::store - PASSO 2: Busca de usuário', [
                'email'      => $request->email,
                'user_found' => $user ? true : false,
                'user_id'    => $user?->id,
                'timestamp'  => now()->toISOString()
            ] );

            if ( !$user ) {
                Log::warning( 'PasswordResetLinkController::store - Tentativa de reset para e-mail não registrado', [
                    'email'      => $request->email,
                    'ip'         => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp'  => now()->toISOString()
                ] );

                // Retornar mensagem genérica por segurança (não revelar se e-mail existe)
                return back()->with( 'status', __( 'passwords.sent' ) );
            }

            // 3. Verificar se usuário está ativo
            if ( !$user->is_active ) {
                Log::warning( 'PasswordResetLinkController::store - Tentativa de reset para usuário inativo', [
                    'user_id'    => $user->id,
                    'email'      => $user->email,
                    'is_active'  => $user->is_active,
                    'ip'         => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp'  => now()->toISOString()
                ] );

                // Retornar mensagem genérica por segurança
                return back()->with( 'status', __( 'passwords.sent' ) );
            }

            Log::info( 'PasswordResetLinkController::store - PASSO 3: Usuário ativo verificado', [
                'user_id'   => $user->id,
                'email'     => $user->email,
                'is_active' => $user->is_active,
                'timestamp' => now()->toISOString()
            ] );

            // 4. Criar token na tabela user_confirmation_tokens (sistema legado)
            $resetToken = generateSecureTokenUrl();

            Log::info( 'PasswordResetLinkController::store - PASSO 4: Token de reset gerado', [
                'user_id'       => $user->id,
                'email'         => $user->email,
                'token_length'  => strlen( $resetToken ),
                'token_format'  => 'base64url',
                'token_preview' => substr( $resetToken, 0, 10 ) . '...',
                'timestamp'     => now()->toISOString()
            ] );

            // 5. Obter tenant do usuário
            $tenant = $user->tenant;

            Log::info( 'PasswordResetLinkController::store - PASSO 5: Tenant obtido', [
                'user_id'      => $user->id,
                'tenant_id'    => $tenant?->id,
                'tenant_found' => $tenant ? true : false,
                'timestamp'    => now()->toISOString()
            ] );

            if ( !$tenant ) {
                Log::error( 'PasswordResetLinkController::store - Tenant não encontrado para usuário', [
                    'user_id'   => $user->id,
                    'email'     => $user->email,
                    'tenant_id' => $user->tenant_id,
                    'timestamp' => now()->toISOString()
                ] );

                return back()->withInput( $request->only( 'email' ) )
                    ->withErrors( [ 'email' => 'Erro ao processar solicitação. Tente novamente mais tarde.' ] );
            }

            // 6. Criar token na tabela user_confirmation_tokens (sistema legado)
            try {
                Log::info( 'PasswordResetLinkController::store - PASSO 6: Criando token na tabela user_confirmation_tokens', [
                    'user_id'   => $user->id,
                    'email'     => $user->email,
                    'tenant_id' => $tenant->id,
                    'timestamp' => now()->toISOString()
                ] );

                $confirmationToken = \App\Models\UserConfirmationToken::create( [
                    'user_id'    => $user->id,
                    'tenant_id'  => $tenant->id,
                    'token'      => $resetToken,
                    'expires_at' => now()->addMinutes( 15 ), // 15 minutos para reset de senha
                    'type'       => \App\Enums\TokenType::PASSWORD_RESET,
                ] );

                Log::info( 'PasswordResetLinkController::store - Token criado com sucesso na tabela user_confirmation_tokens', [
                    'user_id'    => $user->id,
                    'email'      => $user->email,
                    'tenant_id'  => $tenant->id,
                    'token_id'   => $confirmationToken->id,
                    'token_type' => $confirmationToken->type->value,
                    'expires_at' => $confirmationToken->expires_at->toISOString(),
                    'timestamp'  => now()->toISOString()
                ] );

            } catch ( \Throwable $e ) {
                Log::error( 'PasswordResetLinkController::store - Erro ao criar token na tabela user_confirmation_tokens', [
                    'user_id'    => $user->id,
                    'email'      => $user->email,
                    'tenant_id'  => $tenant->id,
                    'error'      => $e->getMessage(),
                    'error_type' => get_class( $e ),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'timestamp'  => now()->toISOString()
                ] );

                return back()->withInput( $request->only( 'email' ) )
                    ->withErrors( [ 'email' => 'Erro ao processar solicitação. Tente novamente mais tarde.' ] );
            }

            // 7. Disparar evento personalizado PasswordResetRequested
            try {
                Log::info( 'PasswordResetLinkController::store - PASSO 7: Disparando evento PasswordResetRequested', [
                    'user_id'   => $user->id,
                    'email'     => $user->email,
                    'tenant_id' => $tenant->id,
                    'token_id'  => $confirmationToken->id,
                    'timestamp' => now()->toISOString()
                ] );

                PasswordResetRequested::dispatch( $user, $resetToken, $tenant );

                Log::info( 'PasswordResetLinkController::store - Evento PasswordResetRequested disparado com sucesso', [
                    'user_id'    => $user->id,
                    'email'      => $user->email,
                    'tenant_id'  => $tenant->id,
                    'token_id'   => $confirmationToken->id,
                    'event_type' => 'password_reset_requested',
                    'timestamp'  => now()->toISOString()
                ] );

            } catch ( \Throwable $e ) {
                Log::error( 'PasswordResetLinkController::store - Erro ao disparar evento PasswordResetRequested', [
                    'user_id'    => $user->id,
                    'email'      => $user->email,
                    'tenant_id'  => $tenant->id,
                    'token_id'   => $confirmationToken->id,
                    'error'      => $e->getMessage(),
                    'error_type' => get_class( $e ),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'timestamp'  => now()->toISOString()
                ] );

                // Mesmo com erro no evento, retornar sucesso para não revelar detalhes
                return back()->with( 'status', __( 'passwords.sent' ) );
            }

            // 7. Log de auditoria - sucesso
            Log::info( 'PasswordResetLinkController::store - PASSO 7: Processo de reset de senha completado com sucesso', [
                'user_id'    => $user->id,
                'email'      => $user->email,
                'tenant_id'  => $tenant->id,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'event_type' => 'password_reset_link_sent',
                'timestamp'  => now()->toISOString()
            ] );

            // 8. Preparar mensagem de sucesso
            $successMessage = __( 'passwords.sent' );

            Log::info( 'PasswordResetLinkController::store - PASSO 8: Preparando resposta com mensagem de sucesso', [
                'user_id'        => $user->id,
                'email'          => $user->email,
                'message'        => $successMessage,
                'message_length' => strlen( $successMessage ),
                'timestamp'      => now()->toISOString()
            ] );

            // 9. Criar resposta de redirecionamento
            $response = back()->with( 'status', $successMessage );

            Log::info( 'PasswordResetLinkController::store - PASSO 9: Resposta de redirecionamento criada', [
                'user_id'       => $user->id,
                'email'         => $user->email,
                'response_type' => get_class( $response ),
                'timestamp'     => now()->toISOString()
            ] );

            // 10. Log final com informações de sessão ANTES de retornar
            Log::info( 'PasswordResetLinkController::store - PASSO 10: Verificação final antes de retornar', [
                'user_id'      => $user->id,
                'email'        => $user->email,
                'session_all'  => session()->all(),
                'has_status'   => session()->has( 'status' ),
                'status_value' => session( 'status' ),
                'timestamp'    => now()->toISOString()
            ] );

            return $response;

        } catch ( \Throwable $e ) {
            Log::error( 'PasswordResetLinkController::store - ERRO GERAL no processo de reset de senha', [
                'email'      => $request->email ?? null,
                'error'      => $e->getMessage(),
                'error_type' => get_class( $e ),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp'  => now()->toISOString()
            ] );

            return back()->withInput( $request->only( 'email' ) )
                ->withErrors( [ 'email' => 'Erro interno do servidor. Tente novamente mais tarde.' ] );
        }
    }

}
