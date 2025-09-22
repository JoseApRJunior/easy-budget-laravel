<?php

declare(strict_types=1);

namespace core\middlewares;

use core\interfaces\MiddlewareInterface;
use core\library\Auth as CoreLibraryAuth;
use http\Redirect;

/**
 * Middleware de autenticação simplificado
 * Mantém compatibilidade com sistema existente
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function execute(): Redirect|null
    {
        // Verifica se a sessão de autenticação existe
        if (!isset($_SESSION['auth'])) {
            return new Redirect('/');
        }

        // Verifica se precisa atualizar a sessão
        if (handleLastUpdateSession('auth')) {
            if (!CoreLibraryAuth::isAuth()) {
                return new Redirect('/');
            }
        }

        // Verifica timeout da sessão
        return handleSessionTimeout();
    }
}