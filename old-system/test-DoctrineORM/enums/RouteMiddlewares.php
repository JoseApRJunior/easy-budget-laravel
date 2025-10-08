<?php

namespace core\enums;

use core\middlewares\AdminORMMiddleware;
use core\middlewares\AuthORMMiddleware;
use core\middlewares\GuestORMMiddleware;
use core\middlewares\ProviderORMMiddleware;
use core\middlewares\UserORMMiddleware;

enum RouteMiddlewares: string
{
    case auth     = AuthORMMiddleware::class;
    case admin    = AdminORMMiddleware::class;
    case guest    = GuestORMMiddleware::class;
    case provider = ProviderORMMiddleware::class;
    case user     = UserORMMiddleware::class;
}
