<?php

declare(strict_types=1);

namespace core\middlewares;

use core\interfaces\MiddlewareInterface;
use core\library\Auth as CoreLibraryAuth;
use http\Redirect;

/**
 * Middleware de visitante simplificado
 */
class GuestMiddleware implements MiddlewareInterface
{
    public function execute(): Redirect|null
    {
        // Se usuário está autenticado, redireciona
        if (CoreLibraryAuth::isAuth()) {
            if (CoreLibraryAuth::isAdmin()) {
                return new Redirect('/admin');
            }
            return new Redirect('/provider');
        }

        return null;
    }
}