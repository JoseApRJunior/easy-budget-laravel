<?php

declare(strict_types=1);

namespace core\middlewares;

use app\database\servicesORM\SessionService;
use app\database\servicesORM\AuthenticationService;
use Doctrine\ORM\EntityManagerInterface;
use http\Redirect;

/**
 * Middleware principal de monitoramento
 *
 * Coleta métricas de todas as requisições automaticamente
 */
class MonitoringMiddleware extends AbstractORMMiddleware
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        SessionService $sessionService,
        AuthService $authService,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct( $sessionService, $authService );
        $this->entityManager = $entityManager;
    }

    /**
     * Executa coleta de métricas
     */
    protected function performCheck(): ?Redirect
    {
        // Middleware de monitoramento não bloqueia requisições
        // Apenas coleta métricas via trait
        return null;
    }

    /**
     * Retorna chave da sessão
     */
    protected function getSessionKey(): string
    {
        return 'monitoring';
    }

}
