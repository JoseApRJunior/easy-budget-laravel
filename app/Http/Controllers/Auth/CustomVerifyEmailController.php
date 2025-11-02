<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\UserConfirmationTokenRepository;
use App\Repositories\UserRepository;
use App\Support\ServiceResult;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller personalizado para verificação de e-mail.
 *
 * Este controller implementa a arquitetura híbrida de verificação de e-mail,
 * substituindo o sistema padrão do Laravel com funcionalidades avançadas:
 * - Integração com sistema multi-tenant
 * - Tratamento robusto de erros
 * - Logging de segurança detalhado
 * - Validação de tokens customizada
 * - Ativação automática de usuários
 *
 * Funcionalidades principais:
 * - Validação de tokens de confirmação
 * - Verificação de usuários e tenants
 * - Marcação de e-mail como verificado
 * - Disparo de eventos Laravel Verified
 * - Limpeza automática de tokens usados
 * - Redirecionamentos com feedback visual
 */
class CustomVerifyEmailController extends Controller
{
    protected UserConfirmationTokenRepository $userConfirmationTokenRepository;
    protected UserRepository                  $userRepository;

    public function __construct(
        UserConfirmationTokenRepository $userConfirmationTokenRepository,
        UserRepository $userRepository,
    ) {
        $this->userConfirmationTokenRepository = $userConfirmationTokenRepository;
        $this->userRepository                  = $userRepository;
    }

    /**
     * Exibir página de confirmação de e-mail.
     *
     * Esta página é mostrada quando o usuário clica no link de verificação
     * do e-mail. O token é passado como parâmetro na query string.
     */
    public function show( Request $request ): View
    {
        $token = $request->query( 'token' );

        if ( !$token ) {
            return view( 'auth.verify-email', [
                'error' => 'Token de verificação ausente.',
                'title' => 'Link inválido'
            ] );
        }

        // Validar formato do token usando a função global
        $sanitizedToken = validateAndSanitizeToken( $token, 'base64url' );
        if ( !$sanitizedToken ) {
            return view( 'auth.verify-email', [
                'error' => 'Token de verificação inválido.',
                'title' => 'Link inválido'
            ] );
        }

        return view( 'auth.verify-email', [
            'token' => $sanitizedToken,
            'title' => 'Verificação de E-mail'
        ] );
    }

    /**
     * Processar confirmação de conta via token.
     *
     * Método principal que implementa toda a lógica de verificação:
     * 1. Valida presença do token na query string
     * 2. Busca UserConfirmationToken válido (não expirado)
     * 3. Verifica se usuário existe e pertence ao tenant correto
     * 4. Marca e-mail como verificado usando método Laravel
     * 5. Dispara evento Verified do Laravel
     * 6. Remove token usado após confirmação
     * 7. Ativa usuário se necessário (is_active = true)
     * 8. Redireciona para login com mensagem de sucesso
     * 9. Tratamento completo de cenários de erro
     *
     * @param Request $request Requisição HTTP com token na query string
     * @return RedirectResponse Redirecionamento com feedback
     */
    public function confirmAccount( Request $request ): RedirectResponse
    {
        // 1. Validar presença do token na query string
        $token = $request->query( 'token' );

        if ( !$token ) {
            $this->logSecurityEvent( 'TOKEN_AUSENTE', null, null, [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ] );

            return $this->redirectError(
                'login',
                'Token de verificação ausente. Solicite um novo link de verificação.',
            );
        }

        // 2. Buscar UserConfirmationToken válido (não expirado)
        $confirmationToken = $this->findValidConfirmationToken( $token, $request );
        if ( !$confirmationToken ) {
            $this->logSecurityEvent( 'TOKEN_INVALIDO', null, null, [
                'token'      => $token,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ] );

            return $this->redirectError(
                'login',
                'Token de verificação inválido ou expirado. Solicite um novo link de verificação.',
            );
        }

        // 3. Verificar se usuário existe e pertence ao tenant correto
        $user = $this->userRepository->find( $confirmationToken->user_id );
        if ( !$user ) {
            $this->logSecurityEvent( 'USUARIO_NAO_ENCONTRADO', $confirmationToken->user_id, $confirmationToken->tenant_id, [
                'token_id' => $confirmationToken->id,
                'ip'       => $request->ip(),
            ] );

            return $this->redirectError(
                'login',
                'Usuário não encontrado. Entre em contato com o suporte.',
            );
        }

        // Verificar se o usuário está ativo
        if ( !$user->is_active ) {
            $this->logSecurityEvent( 'USUARIO_INATIVO', $user->id, $user->tenant_id, [
                'token_id' => $confirmationToken->id,
                'ip'       => $request->ip(),
            ] );

            return $this->redirectError(
                'login',
                'Usuário não encontrado. Entre em contato com o suporte.',
            );
        }

        // Verificar se o usuário pertence ao tenant correto
        if ( $user->tenant_id !== $confirmationToken->tenant_id ) {
            $this->logSecurityEvent( 'TENANT_MISMATCH', $user->id, $confirmationToken->tenant_id, [
                'user_tenant_id'  => $user->tenant_id,
                'token_tenant_id' => $confirmationToken->tenant_id,
                'ip'              => $request->ip(),
            ] );

            return $this->redirectError(
                'login',
                'Erro de validação de segurança. Entre em contato com o suporte.',
            );
        }

        try {
            // 4. Marcar e-mail como verificado usando método Laravel
            $user->markEmailAsVerified();

            // 5. Disparar evento Verified do Laravel
            Event::dispatch( new Verified( $user ) );

            // 6. Remover token usado após confirmação
            $this->userConfirmationTokenRepository->delete( $confirmationToken->id );

            // 7. Ativar usuário se necessário (is_active = true)
            if ( !$user->is_active ) {
                $user->update( [ 'is_active' => true ] );

                $this->logSecurityEvent( 'USUARIO_ATIVADO', $user->id, $user->tenant_id, [
                    'via' => 'email_verification',
                    'ip'  => $request->ip(),
                ] );
            }

            // 8. Logging de segurança/auditoria
            $this->logSecurityEvent( 'EMAIL_VERIFICADO', $user->id, $user->tenant_id, [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'token_id'   => $confirmationToken->id,
            ] );

            // 9. Fazer login automático do usuário após verificação bem-sucedida
            Auth::login( $user );

            // 10. Redirecionar para dashboard com sessão completa
            return $this->redirectSuccess(
                'provider.dashboard',
                'E-mail verificado com sucesso! Bem-vindo ao Easy Budget.',
            );

        } catch ( \Exception $e ) {
            // Tratamento de erros inesperados
            $this->logSecurityEvent( 'ERRO_VERIFICACAO', $user->id, $user->tenant_id, [
                'error' => $e->getMessage(),
                'ip'    => $request->ip(),
                'trace' => $e->getTraceAsString(),
            ] );

            return $this->redirectError(
                'provider.dashboard',
                'Erro interno durante a verificação. Tente novamente ou entre em contato com o suporte.',
            );
        }
    }

    /**
     * Buscar e validar token de confirmação.
     *
     * Método otimizado que busca tokens válidos com validações de segurança:
     * - Verifica se token existe e não está expirado
     * - Valida formato do token (base64url, 43 caracteres)
     * - Sanitiza entrada para prevenir ataques
     * - Usa repository para busca case-insensitive
     * - Logging detalhado para auditoria de segurança
     *
     * @param string $token Token de confirmação a ser validado
     * @param Request $request Requisição HTTP para contexto de segurança
     * @return UserConfirmationToken|null Token válido ou null se inválido/expirado
     */
    private function findValidConfirmationToken( string $token, Request $request ): ?UserConfirmationToken
    {
        // 1. Sanitizar e validar formato do token usando função global segura
        $sanitizedToken = validateAndSanitizeToken( $token, 'base64url' );

        if ( !$sanitizedToken ) {
            $this->logSecurityEvent( 'TOKEN_FORMATO_INVALIDO', null, null, [
                'token_length' => strlen( $token ),
                'ip'           => $request->ip(),
                'user_agent'   => $request->userAgent(),
            ] );

            return null;
        }

        // 2. Buscar token válido no banco de dados usando repository (case-insensitive)
        $confirmationToken = $this->userConfirmationTokenRepository->findByToken( $sanitizedToken );

        // 3. Verificar se token não expirou
        if ( $confirmationToken && $confirmationToken->expires_at->isPast() ) {
            $this->logSecurityEvent( 'TOKEN_EXPIRADO', $confirmationToken->user_id, $confirmationToken->tenant_id, [
                'token_id'   => $confirmationToken->id,
                'expires_at' => $confirmationToken->expires_at,
                'ip'         => $request->ip(),
            ] );

            // Remover token expirado
            $this->userConfirmationTokenRepository->delete( $confirmationToken->id );
            $confirmationToken = null;
        }

        // 4. Logging detalhado para auditoria
        if ( $confirmationToken ) {
            $this->logSecurityEvent( 'TOKEN_VALIDO_ENCONTRADO', $confirmationToken->user_id, $confirmationToken->tenant_id, [
                'token_id'   => $confirmationToken->id,
                'expires_at' => $confirmationToken->expires_at,
                'ip'         => $request->ip(),
            ] );
        } else {
            $this->logSecurityEvent( 'TOKEN_INVALIDO_OU_EXPIRADO', null, null, [
                'token_prefix' => substr( $sanitizedToken, 0, 8 ) . '...',
                'ip'           => $request->ip(),
                'user_agent'   => $request->userAgent(),
            ] );
        }

        return $confirmationToken;
    }

    /**
     * Log detalhado de eventos de segurança.
     *
     * @param string $event Tipo do evento
     * @param int|null $userId ID do usuário (opcional)
     * @param int|null $tenantId ID do tenant (opcional)
     * @param array $context Contexto adicional
     */
    private function logSecurityEvent( string $event, ?int $userId, ?int $tenantId, array $context = [] ): void
    {
        Log::channel( 'security' )->info( "EmailVerification: {$event}", [
            'event'     => $event,
            'user_id'   => $userId,
            'tenant_id' => $tenantId,
            'timestamp' => now(),
            'context'   => $context,
        ] );
    }

}
