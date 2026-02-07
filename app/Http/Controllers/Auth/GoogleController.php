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
    private OAuthClientInterface $oauthClient;

    private SocialAuthenticationInterface $socialAuthService;

    public function __construct(
        OAuthClientInterface $oauthClient,
        SocialAuthenticationInterface $socialAuthService,
    ) {
        $this->oauthClient = $oauthClient;
        $this->socialAuthService = $socialAuthService;
    }

    /**
     * Redireciona o usuário para o Google OAuth
     */
    public function redirect(): RedirectResponse
    {
        // Verifica se o cliente OAuth está configurado
        if (! $this->oauthClient->isConfigured()) {
            Log::warning('Tentativa de acesso ao Google OAuth sem configuração', [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return redirect()->route('home')->withErrors(['error' => 'Serviço de autenticação Google não está configurado.']);
        }

        Log::info('Iniciando autenticação Google OAuth', [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $this->oauthClient->redirectToProvider();
    }

    /**
     * Processa o callback do Google OAuth
     */
    public function callback(Request $request): RedirectResponse
    {
        // Verifica se há erro no callback (usuário cancelou)
        if ($request->has('error')) {
            Log::info('Usuário cancelou autenticação Google OAuth', [
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('home')->withErrors(['error' => 'Autenticação cancelada pelo usuário.']);
        }

        // Verifica se o cliente OAuth está configurado
        if (! $this->oauthClient->isConfigured()) {
            Log::error('Callback do Google OAuth recebido sem configuração', [
                'ip' => $request->ip(),
            ]);

            return redirect()->route('home')->withErrors(['error' => 'Serviço de autenticação não configurado.']);
        }

        // Processa dados do usuário do Google
        $googleUserData = $this->oauthClient->handleProviderCallback($request);

        // Autentica ou cria usuário através do serviço de autenticação social
        $authResult = $this->socialAuthService->authenticateWithSocialProvider('google', $googleUserData);

        if (! $authResult->isSuccess()) {
            return redirect()->route('login')->withErrors(['email' => $authResult->getMessage()]);
        }

        // Loga o usuário
        $user = $authResult->getData();
        Auth::login($user);

        // Mensagem personalizada para novos registros
        $message = $user->wasRecentlyCreated
            ? 'Cadastro realizado com sucesso! Bem-vindo ao Easy Budget. Configure sua conta para começar.'
            : 'Bem-vindo de volta! Login realizado com sucesso via Google.';

        return redirect()->intended(route('provider.dashboard', absolute: false))
            ->with('success', $message);
    }

    /**
     * Garante sessão limpa para evitar conflitos
     *
     * Remove dados de sessão antigos e garante que apenas
     * dados necessários estejam presentes.
     */
    private function ensureCleanSession(Request $request): void
    {
        // Remove dados de sessão anteriores que podem causar conflitos
        session()->forget([
            'previous_login_method',
            'old_session_data',
            'temp_oauth_data',
        ]);

        // Garante que dados críticos estejam presentes
        $user = Auth::user();

        if (! $user) {
            Log::warning('Tentativa de limpeza de sessão sem usuário autenticado');

            return;
        }

        // Atualiza timestamp da sessão
        session([
            'last_activity' => now()->toISOString(),
            'session_validated' => true,
        ]);

        Log::info('Sessão limpa garantida para Google OAuth', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);
    }

    /**
     * Desvincula a conta Google do usuário
     */
    public function unlink(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Verifica se o usuário tem conta Google vinculada
        if (! $user->google_id) {
            Log::info('Tentativa de desvinculação sem conta Google', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return redirect()->back()->with('error', 'Nenhuma conta Google vinculada para desvincular.');
        }

        // Desvincula a conta Google
        $user->update([
            'google_id' => null,
            'avatar' => null,
            'google_data' => null,
        ]);

        Log::info('Conta Google desvinculada com sucesso', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Conta Google desvinculada com sucesso.');
    }
}
