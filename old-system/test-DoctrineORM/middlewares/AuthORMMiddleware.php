<?php

declare(strict_types=1);

namespace core\middlewares;

use http\Redirect;

/**
 * Middleware de autenticação seguindo padrão ORM da documentação
 */
class AuthORMMiddleware extends AbstractORMMiddleware
{
    /**
     * Executa a verificação específica do middleware auth
     *
     * @return Redirect|null
     */
    protected function performCheck(): Redirect|null
    {
        $isApiRequest = (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) || (
            isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')
        );

        // Se não estiver autenticado, trata a resposta
        if (!$this->isAuthenticated()) {
            if ($isApiRequest) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Authentication required.']);
                exit;
            }
            return $this->createRedirect('/');
        }

        // Verifica o timeout da sessão
        $timeoutResult = $this->checkSessionTimeout();
        if ($timeoutResult) {
            if ($isApiRequest) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session timed out.']);
                exit;
            }
            return $timeoutResult;
        }

        // Se tudo estiver OK, permite o acesso
        return null;
    }
    
    /**
     * Retorna a chave da sessão para este middleware
     *
     * @return string
     */
    protected function getSessionKey(): string
    {
        return 'auth';
    }
}