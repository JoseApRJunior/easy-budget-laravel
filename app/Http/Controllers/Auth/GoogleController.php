<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Contracts\Interfaces\Auth\OAuthClientInterface;
use App\Contracts\Interfaces\Auth\SocialAuthenticationInterface;
use App\Http\Controllers\Abstracts\Controller;
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

    public function __construct(
        OAuthClientInterface $oauthClient,
        SocialAuthenticationInterface $socialAuthService,
    ) {
        $this->oauthClient       = $oauthClient;
        $this->socialAuthService = $socialAuthService;
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

            // Cria sessão customizada para compatibilidade com sistema existente
            $this->createCustomSession( $request );

            // Garante sessão limpa para evitar conflitos
            $this->ensureCleanSession( $request );

            Log::info( 'Usuário autenticado com sucesso via Google OAuth', [
                'user_id' => $authResult->getData()->id,
                'email'   => $authResult->getData()->email,
                'ip'      => $request->ip(),
            ] );

            // Redireciona para o dashboard
            return redirect()->route( 'dashboard' )->with( 'success', $authResult->getMessage() );

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
     * Cria sessão customizada para compatibilidade com sistema existente
     *
     * Este método garante que a sessão tenha todos os dados necessários
     * para o funcionamento correto do sistema após login via Google OAuth.
     *
     * @param Request $request
     * @return void
     */
    private function createCustomSession( Request $request ): void
    {
        try {
            $user = Auth::user();

            // Dados essenciais da sessão
            session( [
                'auth'                => true, // Sessão necessária para o menu de navegação
                'user_id'             => $user->id,
                'user_name'           => $user->name ?? $user->email,
                'user_email'          => $user->email,
                'user_role'           => $user->role ?? 'provider',
                'tenant_id'           => $user->tenant_id,
                'login_method'        => 'google_oauth',
                'login_time'          => now()->toISOString(),
                'session_fingerprint' => $this->generateSessionFingerprint( $request ),
            ] );

            // Dados específicos do provider se aplicável
            if ( $user->tenant_id ) {
                $provider = $user->provider ?? null;
                if ( $provider ) {
                    session( [
                        'provider_id'    => $provider->id,
                        'provider_name'  => $provider->company_name ?? $user->name,
                        'provider_logo'  => $provider->logo ?? $user->logo,
                        'terms_accepted' => $provider->terms_accepted,
                    ] );
                }
            }

            Log::info( 'Sessão customizada criada para Google OAuth', [
                'user_id'   => $user->id,
                'tenant_id' => $user->tenant_id,
                'ip'        => $request->ip(),
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao criar sessão customizada Google OAuth', [
                'error'   => $e->getMessage(),
                'user_id' => Auth::id(),
                'ip'      => $request->ip(),
            ] );
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

}
