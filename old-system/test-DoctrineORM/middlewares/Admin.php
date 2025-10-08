<?php

declare(strict_types=1);

namespace core\middlewares;

use core\interfaces\MiddlewareInterface;
use core\library\Auth as CoreLibraryAuth;
use http\Redirect;

/**
 * Middleware de Administrador
 */
class Admin implements MiddlewareInterface
{
    public function execute(): Redirect|null
    {
        // Verifica se precisa atualizar a sessão de admin
        if (handleLastUpdateSession('admin')) {
            if (!CoreLibraryAuth::isAdmin()) {
                return new Redirect('/');
            }
        }

        return null;
    }
}