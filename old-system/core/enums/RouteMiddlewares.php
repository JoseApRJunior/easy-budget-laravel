<?php

namespace core\enums;

use core\middlewares\Admin;
use core\middlewares\Auth;
use core\middlewares\Guest;
use core\middlewares\Provider;
use core\middlewares\User;

enum RouteMiddlewares: string
{
    case auth = Auth::class;
    case admin = Admin::class;
    case guest = Guest::class;
    case provider = Provider::class;
    case user = User::class;
}
