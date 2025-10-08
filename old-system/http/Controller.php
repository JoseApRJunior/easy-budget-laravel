<?php

namespace http;

use core\library\Response;
use DI\Container;
use Exception;

class Controller
{
    private mixed $response = null;

    private function controllerPath(Route $route, $controller)
    {
        return ($route->getRouteOptionsInstance() && $route->getRouteOptionsInstance()->optionsExist('controller')) ?
            "app\\controllers\\" . $route->getRouteOptionsInstance()->execute('controller') . "\\" . $controller :
            "app\\controllers\\$controller";
    }

    public function call(Route $route, Container $container, $request)
    {
        $controller = $route->controller;

        if (!str_contains($controller, ':')) {
            throw new Exception("Invalid controller name -> $controller <- in route: " . $route->getRouteUriInstance()->getUri());
        }

        [ $controller, $action ] = explode(':', $controller);

        $controllerInstance = $this->controllerPath($route, $controller);

        if (!class_exists($controllerInstance)) {
            if (env('APP_ENV') === 'production') {
                // Evitar recursão infinita
                if ($controller !== 'ErrorController') {
                    return (new Controller())->call(new Route('GET', 'ErrorController:notFound', []), $container, $request);
                }

                // Se ErrorController também falhar, retorna resposta básica
                return new Response('Page not found', 404);
            } else {
                throw new Exception("Controller class does not exist -> $controllerInstance <- in route: " . $route->getRouteUriInstance()->getUri());
            }
        }

        $controller = $container->get($controllerInstance);

        if (!method_exists($controller, $action)) {
            if (env('APP_ENV') === 'production') {
                // Evitar recursão infinita
                if ($controller !== 'ErrorController') {
                    return (new Controller())->call(new Route('GET', 'ErrorController:methodNotAllowed', []), $container, $request);
                }

                // Fallback se ErrorController falhar
                return new Response('Method not allowed', 405);
            } else {
                throw new Exception("Action method does not exist -> $action() <- in controller: $controllerInstance");
            }
        }

        // if ( $route->getRouteOptionsInstance()?->optionsExist( 'middlewares' ) ) {
        //     $this->response = ( new Middleware( $route->getRouteOptionsInstance()->execute( 'middlewares' ), $container ) )->execute();
        // }
        if ($route->getRouteOptionsInstance()?->optionsExist('middlewares')) {
            $middlewareResponse = (new Middleware($route->getRouteOptionsInstance()->execute('middlewares'), $container))->execute();

            // Validar se middleware retornou resposta válida
            if ($middlewareResponse instanceof Redirect || $middlewareResponse instanceof Response) {
                $this->response = $middlewareResponse;
            }
        }

        if (!$this->response instanceof Redirect) {
            $this->response = $container->call([ $controller, $action ], $route->getRouteWildcardInstance()?->getParams() ?? []);
        }

        if (!$this->response || !$this->response instanceof Response) {
            throw new Exception("Response not found in controller: " . get_class($controller) . " and method: " . $action);
        }

        echo $this->response->send();
    }

}
