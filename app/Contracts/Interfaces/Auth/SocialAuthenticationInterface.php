<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces\Auth;

use App\Models\User;
use App\Support\ServiceResult;

/**
 * Interface para serviços de autenticação social
 *
 * Esta interface define o contrato para implementação de serviços
 * de autenticação através de provedores sociais (Google, Facebook, etc.),
 * seguindo os padrões arquiteturais do projeto Easy Budget Laravel.
 */
interface SocialAuthenticationInterface
{
    /**
     * Autentica usuário através de provedor social
     *
     * @param string $provider Nome do provedor (google, facebook, etc.)
     * @param array $userData Dados do usuário do provedor
     * @return ServiceResult
     */
    public function authenticateWithSocialProvider( string $provider, array $userData ): ServiceResult;

    /**
     * Cria novo usuário a partir de dados sociais
     *
     * @param string $provider Nome do provedor
     * @param array $userData Dados do usuário do provedor
     * @return ServiceResult
     */
    public function createUserFromSocialData( string $provider, array $userData ): ServiceResult;

    /**
     * Vincula conta social a usuário existente
     *
     * @param User $user Usuário existente
     * @param string $provider Nome do provedor
     * @param array $userData Dados do usuário do provedor
     * @return ServiceResult
     */
    public function linkSocialAccountToUser( User $user, string $provider, array $userData ): ServiceResult;

    /**
     * Busca usuário por ID social
     *
     * @param string $provider Nome do provedor
     * @param string $socialId ID do usuário no provedor social
     * @return User|null
     */
    public function findUserBySocialId( string $provider, string $socialId ): ?User;

    /**
     * Sincroniza dados do perfil social com usuário existente
     *
     * @param User $user Usuário existente
     * @param array $socialData Dados atualizados do provedor social
     * @return ServiceResult
     */
    public function syncSocialProfileData( User $user, array $socialData ): ServiceResult;

    /**
     * Valida se o e-mail do provedor social já está em uso
     *
     * @param string $email E-mail do provedor social
     * @param string|null $excludeUserId ID do usuário para excluir da validação (para updates)
     * @return bool
     */
    public function isSocialEmailInUse( string $email, ?string $excludeUserId = null ): bool;

    /**
     * Obtém lista de provedores sociais suportados
     *
     * @return array
     */
    public function getSupportedProviders(): array;
}
