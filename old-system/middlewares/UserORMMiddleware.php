<?php

declare(strict_types=1);

namespace core\middlewares;

use http\Redirect;

/**
 * Middleware de usuário seguindo padrão ORM da documentação
 */
class UserORMMiddleware extends AbstractORMMiddleware
{
    /**
     * Executa a verificação específica do middleware user
     *
     * @return Redirect|null
     */
    protected function performCheck(): Redirect|null
    {
        // Verifica se está autenticado
        if (!$this->isAuthenticated()) {
            return $this->createRedirect('/');
        }
        
        // Verifica se tem sessão válida
        if (!$this->validateCurrentSession()) {
            return $this->createRedirect('/');
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
        return 'user';
    }
}