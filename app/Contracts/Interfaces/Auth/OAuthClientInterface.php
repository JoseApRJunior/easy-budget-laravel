<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Interface para clientes OAuth (Google, Facebook, etc.)
 *
 * Esta interface define o contrato para implementação de clientes OAuth,
 * seguindo os padrões arquiteturais do projeto Easy Budget Laravel.
 */
interface OAuthClientInterface
{
    /**
     * Redireciona o usuário para o provedor OAuth
     *
     * @return RedirectResponse
     */
    public function redirectToProvider(): RedirectResponse;

    /**
     * Processa o callback do provedor OAuth
     *
     * @param Request $request
     * @return array Dados do usuário do provedor OAuth
     */
    public function handleProviderCallback( Request $request ): array;

    /**
     * Obtém informações básicas do usuário do provedor
     *
     * @param string $accessToken Token de acesso do provedor
     * @return array Dados básicos do usuário (id, name, email, avatar)
     */
    public function getUserInfo( string $accessToken ): array;

    /**
     * Valida se o provedor está configurado corretamente
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Obtém o nome do provedor (google, facebook, etc.)
     *
     * @return string
     */
    public function getProviderName(): string;
}
