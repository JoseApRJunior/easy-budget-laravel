<?php

declare(strict_types=1);

namespace core\middlewares;

use http\Redirect;

/**
 * Middleware de provider seguindo padrão ORM da documentação
 */
class ProviderORMMiddleware extends AbstractORMMiddleware
{
    /**
     * Executa a verificação específica do middleware provider
     *
     * @return Redirect|null
     */
    protected function performCheck(): Redirect|null
    {
        // Admin bypassa verificações
        if ( $this->isAdmin() ) {
            return null;
        }

        // Verifica se está autenticado
        if ( !$this->isAuthenticated() ) {
            return $this->createRedirect( '/' );
        }

        // Verifica se é provider via AuthenticationService
        $userId = $this->getCurrentUserId();
        if ( !$userId ) {
            return $this->createRedirect( '/' );
        }

        // Usar AuthService para validar provider
        if ( !$this->authService->isProvider() ) {
            return $this->createRedirect( '/' );
        }

        return null;
    }

    /**
     * Retorna a chave da sessão para este middleware
     *
     * @return string
     */
    protected function getSessionKey(): string
    {
        return 'provider';
    }

}
