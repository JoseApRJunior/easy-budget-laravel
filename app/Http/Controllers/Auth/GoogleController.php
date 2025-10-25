<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Contracts\Interfaces\Auth\OAuthClientInterface;
use App\Contracts\Interfaces\Auth\SocialAuthenticationInterface;
use App\Http\Controllers\Abstracts\Controller;
use App\Repositories\UserRepository;
use App\Services\Application\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Controller para autenticação Google OAuth
 *
 * Esta classe gerencia o fluxo de autenticação através do Google OAuth 2.0,
 * seguindo os padrões arquiteturais do projeto Easy Budget Laravel.
 */
class GoogleController extends Controller
{
    private OAuthClientInterface          $oauthClient;
    private SocialAuthenticationInterface $socialAuthService;
    private EmailVerificationService      $emailVerificationService;
    private UserRepository                $userRepository;

    public function __construct(
        OAuthClientInterface $oauthClient,
        SocialAuthenticationInterface $socialAuthService,
        EmailVerificationService $emailVerificationService,
        UserRepository $userRepository,
    ) {
        $this->oauthClient              = $oauthClient;
        $this->socialAuthService        = $socialAuthService;
        $this->emailVerificationService = $emailVerificationService;
        $this->userRepository           = $userRepository;
    }

    /**
     * Redireciona o usuário para o Google OAuth
     *
     * @return RedirectResponse
     */
    public function redirect(): RedirectResponse
    {
        // Verifica se o cliente OAuth está configurado
        if ( !$this->oauthClient->isConfigured() ) {
            Log::warning( 'Tentativa de acesso ao Google OAuth sem configuração', [
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
            ] );

            return redirect()->route( 'home' )->with( 'error', 'Serviço de autenticação Google não está configurado.' );
        }

        Log::info( 'Iniciando autenticação Google OAuth', [
            'ip'         => request()->ip(),
            'user_agent' => request()->userAgent(),
        ] );

        return $this->oauthClient->redirectToProvider();
    }

    /**
     * Processa o callback do Google OAuth
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function callback( Request $request ): RedirectResponse
    {
        try {
            // Verifica se há erro no callback (usuário cancelou)
            if ( $request->has( 'error' ) ) {
                Log::info( 'Usuário cancelou autenticação Google OAuth', [
                    'error'             => $request->get( 'error' ),
                    'error_description' => $request->get( 'error_description' ),
                    'ip'                => $request->ip(),
                ] );

                return redirect()->route( 'home' )->with( 'error', 'Autenticação cancelada pelo usuário.' );
            }

            // Verifica se o cliente OAuth está configurado
            if ( !$this->oauthClient->isConfigured() ) {
                Log::error( 'Callback do Google OAuth recebido sem configuração', [
                    'ip' => $request->ip(),
                ] );

                return redirect()->route( 'home' )->with( 'error', 'Serviço de autenticação não configurado.' );
            }

            // Processa dados do usuário do Google
            $googleUserData = $this->oauthClient->handleProviderCallback( $request );

            // Autentica ou cria usuário através do serviço de autenticação social
            $authResult = $this->socialAuthService->authenticateWithSocialProvider( 'google', $googleUserData );

            if ( !$authResult->isSuccess() ) {
                Log::error( 'Falha na autenticação social Google', [
                    'error' => $authResult->getMessage(),
                    'ip'    => $request->ip(),
                ] );

                return redirect()->route( 'home' )->with( 'error', $authResult->getMessage() );
            }

            // Loga o usuário
            Auth::login( $authResult->getData() );

            // Garante sessão limpa para evitar conflitos
            $this->ensureCleanSession( $request );

            Log::info( 'Usuário autenticado com sucesso via Google OAuth', [
                'user_id' => $authResult->getData()->id,
                'email'   => $authResult->getData()->email,
                'ip'      => $request->ip(),
            ] );

            // Redireciona para o dashboard
            return redirect()->route( 'provider.dashboard' )->with( 'success', $authResult->getMessage() );

        } catch ( \Exception $e ) {
            Log::error( 'Erro no callback do Google OAuth', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'ip'    => $request->ip(),
            ] );

            return redirect()->route( 'home' )->with( 'error', 'Erro interno durante a autenticação. Tente novamente.' );
        }
    }

    /**
     * Garante sessão limpa para evitar conflitos
     *
     * Remove dados de sessão antigos e garante que apenas
     * dados necessários estejam presentes.
     *
     * @param Request $request
     * @return void
     */
    private function ensureCleanSession( Request $request ): void
    {
        try {
            // Remove dados de sessão anteriores que podem causar conflitos
            session()->forget( [
                'previous_login_method',
                'old_session_data',
                'temp_oauth_data',
            ] );

            // Garante que dados críticos estejam presentes
            $user = Auth::user();

            if ( !$user ) {
                Log::warning( 'Tentativa de limpeza de sessão sem usuário autenticado' );
                return;
            }

            // Atualiza timestamp da sessão
            session( [
                'last_activity'     => now()->toISOString(),
                'session_validated' => true,
            ] );

            Log::info( 'Sessão limpa garantida para Google OAuth', [
                'user_id' => $user->id,
                'ip'      => $request->ip(),
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao garantir sessão limpa Google OAuth', [
                'error'   => $e->getMessage(),
                'user_id' => Auth::id(),
                'ip'      => $request->ip(),
            ] );
        }
    }

    /**
     * Gera fingerprint único da sessão para segurança
     *
     * Combina dados do usuário e requisição para criar
     * identificador único da sessão.
     *
     * @param Request $request
     * @return string
     */
    private function generateSessionFingerprint( Request $request ): string
    {
        $user = Auth::user();

        $fingerprintData = [
            'user_id'    => $user->id,
            'user_agent' => $request->userAgent(),
            'ip'         => $request->ip(),
            'timestamp'  => now()->timestamp,
        ];

        return hash( 'sha256', json_encode( $fingerprintData ) );
    }

    /**
     * Desvincula a conta Google do usuário
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function unlink( Request $request ): RedirectResponse
    {
        try {
            $user = Auth::user();

            // Verifica se o usuário tem conta Google vinculada
            if ( !$user->google_id ) {
                Log::info( 'Tentativa de desvinculação sem conta Google', [
                    'user_id' => $user->id,
                    'ip'      => $request->ip(),
                ] );

                return redirect()->back()->with( 'error', 'Nenhuma conta Google vinculada para desvincular.' );
            }

            // Desvincula a conta Google usando o repository
            $this->userRepository->update( $user->id, [
                'google_id'   => null,
                'avatar'      => null,
                'google_data' => null,
            ] );

            Log::info( 'Conta Google desvinculada com sucesso', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'ip'      => $request->ip(),
            ] );

            return redirect()->back()->with( 'success', 'Conta Google desvinculada com sucesso.' );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao desvincular conta Google', [
                'error'   => $e->getMessage(),
                'user_id' => Auth::id(),
                'ip'      => $request->ip(),
            ] );

            return redirect()->back()->with( 'error', 'Erro ao desvincular conta Google. Tente novamente.' );
        }
    }

    /**
     * Confirma a vinculação de conta social através de token
     *
     * @param string $token Token de confirmação
     * @return RedirectResponse
     */
    public function confirmLinking( string $token ): RedirectResponse
    {
        try {
            // Valida o token usando EmailVerificationService
            $tokenResult = $this->emailVerificationService->findValidToken( $token );

            if ( !$tokenResult->isSuccess() ) {
                Log::warning( 'Tentativa de confirmação de vinculação com token inválido', [
                    'token' => substr( $token, 0, 10 ) . '...',
                    'error' => $tokenResult->getMessage(),
                    'ip'    => request()->ip(),
                ] );

                return redirect()->route( 'home' )->with( 'error', $tokenResult->getMessage() );
            }

            $tokenData         = $tokenResult->getData();
            $user              = $tokenData[ 'user' ];
            $confirmationToken = $tokenData[ 'token' ];

            // Verifica se o token é do tipo social_linking
            if ( $confirmationToken->type !== 'social_linking' ) {
                Log::warning( 'Token usado para confirmação não é do tipo social_linking', [
                    'token_id'   => $confirmationToken->id,
                    'token_type' => $confirmationToken->type,
                    'user_id'    => $user->id,
                    'ip'         => request()->ip(),
                ] );

                return redirect()->route( 'home' )->with( 'error', 'Link de confirmação inválido. Solicite uma nova vinculação de conta.' );
            }

            // Decodifica os metadados do token
            $metadata = json_decode( $confirmationToken->metadata, true );
            if ( !$metadata || !isset( $metadata[ 'provider' ], $metadata[ 'social_id' ] ) ) {
                Log::error( 'Metadados do token de vinculação incompletos', [
                    'token_id' => $confirmationToken->id,
                    'metadata' => $metadata,
                    'user_id'  => $user->id,
                    'ip'       => request()->ip(),
                ] );

                return redirect()->route( 'home' )->with( 'error', 'Informações de vinculação incompletas. Tente fazer login novamente com sua conta Google.' );
            }

            // Vincula a conta social ao usuário
            $provider = $metadata[ 'provider' ];
            $socialId = $metadata[ 'social_id' ];

            // Atualiza o usuário com os dados do provider social
            $user->update( [
                'google_id'   => $provider === 'google' ? $socialId : $user->google_id,
                'avatar'      => $metadata[ 'social_avatar' ] ?? $user->avatar,
                'google_data' => json_encode( $metadata ),
            ] );

            // Remove o token após uso bem-sucedido
            $this->emailVerificationService->removeToken( $confirmationToken );

            Log::info( 'Vinculação de conta social confirmada com sucesso', [
                'user_id'   => $user->id,
                'email'     => $user->email,
                'provider'  => $provider,
                'social_id' => $socialId,
                'ip'        => request()->ip(),
            ] );

            return redirect()->route( 'provider.dashboard' )->with( 'success', 'Conta ' . ucfirst( $provider ) . ' vinculada com sucesso!' );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao confirmar vinculação de conta social', [
                'token' => substr( $token, 0, 10 ) . '...',
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'ip'    => request()->ip(),
            ] );

            return redirect()->route( 'home' )->with( 'error', 'Erro ao confirmar vinculação da conta. Tente fazer login novamente com sua conta Google.' );
        }
    }

}
