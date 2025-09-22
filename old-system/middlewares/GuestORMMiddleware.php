<?php

declare(strict_types=1);

namespace core\middlewares;

use core\interfaces\MiddlewareInterface;
use core\traits\MetricsCollectionTrait;
use http\Redirect;

/**
 * Middleware de guest - não precisa herdar de AbstractORMMiddleware
 * pois guests não têm sessão ORM
 */
class GuestORMMiddleware implements MiddlewareInterface
{
    use MetricsCollectionTrait;
    
    /**
     * Executa verificação de guest (usuário não autenticado)
     *
     * @return Redirect|null
     */
    public function execute(): Redirect|null
    {
        $this->startMetricsCollection();
        
        try {
            // Se estiver autenticado, redireciona para área logada
            if (isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
                $this->endMetricsCollection('guest', 302);
                return new Redirect('/dashboard');
            }
            
            $this->endMetricsCollection('guest', 200);
            return null;
            
        } catch (\Exception $e) {
            $this->endMetricsCollection('guest', 500);
            return new Redirect('/');
        }
    }
}