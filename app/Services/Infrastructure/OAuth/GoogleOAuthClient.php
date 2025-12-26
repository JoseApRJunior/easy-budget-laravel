<?php

declare(strict_types=1);

namespace App\Services\Infrastructure\OAuth;

use App\Contracts\Interfaces\Auth\OAuthClientInterface;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * Cliente OAuth para integração com Google
 *
 * Esta classe implementa a integração com o Google OAuth 2.0
 * seguindo os padrões arquiteturais do projeto Easy Budget Laravel.
 */
class GoogleOAuthClient implements OAuthClientInterface
{
    /**
     * Redireciona o usuário para o provedor OAuth
     */
    public function redirectToProvider(): RedirectResponse
    {
        Log::info('Redirecionando usuário para autenticação Google OAuth', [
            'provider' => $this->getProviderName(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return Socialite::driver('google')
            ->setHttpClient($this->getHttpClient())
            ->redirect();
    }

    /**
     * Processa o callback do provedor OAuth
     *
     * @return array Dados do usuário do provedor OAuth
     */
    public function handleProviderCallback(Request $request): array
    {
        try {
            $googleUser = Socialite::driver('google')
                ->setHttpClient($this->getHttpClient())
                ->user();

            Log::info('Callback do Google OAuth processado com sucesso', [
                'provider' => $this->getProviderName(),
                'google_id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
                'ip' => $request->ip(),
            ]);

            return [
                'id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
                'verified' => true, // Google já verifica e-mails
            ];
        } catch (\Exception $e) {
            Log::error('Erro no callback do Google OAuth', [
                'provider' => $this->getProviderName(),
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtém informações básicas do usuário do provedor
     *
     * @param  string  $accessToken  Token de acesso do provedor
     * @return array Dados básicos do usuário (id, name, email, avatar)
     */
    public function getUserInfo(string $accessToken): array
    {
        try {
            $googleUser = Socialite::driver('google')
                ->setHttpClient($this->getHttpClient())
                ->userFromToken($accessToken);

            return [
                'id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao obter informações do usuário Google', [
                'provider' => $this->getProviderName(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Valida se o provedor está configurado corretamente
     */
    public function isConfigured(): bool
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect');

        return ! empty($clientId) && ! empty($clientSecret) && ! empty($redirectUri);
    }

    /**
     * Obtém o nome do provedor (google, facebook, etc.)
     */
    public function getProviderName(): string
    {
        return 'google';
    }

    private function getHttpClient(): Client
    {
        $cacertPath = config('services.cacert_path');

        if ($cacertPath && file_exists($cacertPath)) {
            Log::info('Usando CA bundle customizado para Socialite', ['path' => $cacertPath]);

            return new Client(['verify' => $cacertPath]);
        }

        Log::warning('CA bundle não configurado ou inválido, desabilitando verificação de certificado temporariamente', [
            'configured_path' => $cacertPath,
        ]);

        return new Client(['verify' => false]);
    }
}
