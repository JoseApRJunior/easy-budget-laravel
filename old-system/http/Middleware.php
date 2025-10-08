<?php

namespace http;

use core\enums\RouteMiddlewares;
use core\interfaces\MiddlewareInterface;
use DI\Container;
use Exception;

class Middleware
{
    private string $middlewareClass;

    public function __construct(
        protected array $middlewares,
        protected Container $container,
    ) {
    }

    public function middlewareExist($middleware)
    {
        $caseMiddleware = RouteMiddlewares::cases();

        return array_filter(
            $caseMiddleware,
            function (RouteMiddlewares $middlewareCase) use ($middleware) {
                if ($middlewareCase->name === $middleware) {
                    $this->middlewareClass = $middlewareCase->value;

                    return true;
                }

                return false;
            }
        );
    }

    public function execute(): Redirect|null
    {
        foreach ($this->middlewares as $middleware) {
            if (!$this->middlewareExist($middleware)) {
                throw new Exception("Middleware '$middleware' does not exist.");
            }

            $class = $this->middlewareClass;

            if (!class_exists($class)) {
                throw new Exception("Class '$class' does not exist.");
            }

            $middlewareClass = $this->container->get($this->middlewareClass);

            if (!$middlewareClass instanceof MiddlewareInterface) {
                throw new Exception("Middleware '$class' must implement MiddlewareInterface.");
            }

            $return = $middlewareClass->execute();
            if ($return instanceof Redirect) {
                return $return;
            }
        }

        return null;
    }

}
