<?php

declare(strict_types=1);

namespace core\middlewares;

use core\interfaces\MiddlewareInterface;
use core\traits\MiddlewareMonitoringTrait;
use http\Redirect;

/**
 * Middleware de Usuário
 *
 * Gerencia funcionalidades específicas para usuários
 * autenticados no sistema Easy Budget.
 */
class User implements MiddlewareInterface
{
    use MiddlewareMonitoringTrait;

    /**
     * Executa as verificações e atualizações de usuário
     *
     * @return Redirect|null
     */
    public function execute(): Redirect|null
    {
        return $this->executeWithMonitoring(function() {
            // Atualiza a sessão do usuário
            handleLastUpdateSession('user');

            return null;
        });
    }
}
