<?php

declare(strict_types=1);

namespace core\middlewares;

use core\interfaces\MiddlewareInterface;
use core\library\Auth as CoreLibraryAuth;
use http\Redirect;

/**
 * Middleware de usuário simplificado
 */
class UserMiddleware implements MiddlewareInterface
{
    public function execute(): Redirect|null
    {
        // Verifica se precisa atualizar a sessão
        if (handleLastUpdateSession('user')) {
            if (!CoreLibraryAuth::isAuth()) {
                return new Redirect('/login');
            }
        }

        return null;
    }
}