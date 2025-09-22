<?php

declare(strict_types=1);

namespace core\middlewares;

use core\interfaces\MiddlewareInterface;
use core\library\Auth as CoreLibraryAuth;
use http\Redirect;

/**
 * Middleware de provedor simplificado
 * Usa a lógica do Provider.php original
 */
class ProviderMiddleware implements MiddlewareInterface
{
    public function execute(): Redirect|null
    {
        // Admin bypassa todas as verificações
        if (CoreLibraryAuth::isAdmin()) {
            return null;
        }

        // Verifica se precisa atualizar a sessão de provider
        if (handleLastUpdateSession('provider')) {
            if (!CoreLibraryAuth::isProvider()) {
                return new Redirect('/login');
            }
        }

        return null;
    }
}