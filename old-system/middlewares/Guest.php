<?php

declare(strict_types=1);

namespace core\middlewares;

use core\interfaces\MiddlewareInterface;
use core\traits\MiddlewareMonitoringTrait;
use http\Redirect;

/**
 * Middleware de Convidado
 *
 * Gerencia funcionalidades para usuários não autenticados
 * (visitantes) no sistema Easy Budget.
 */
class Guest implements MiddlewareInterface
{
    use MiddlewareMonitoringTrait;

    /**
     * Executa as verificações e atualizações para convidados
     *
     * @return Redirect|null
     */
    public function execute(): Redirect|null
    {
        return $this->executeWithMonitoring(function() {
            // Atualiza a sessão do convidado
            handleLastUpdateSession('guest');

            return null;
        });
    }
}