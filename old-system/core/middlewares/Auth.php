<?php

namespace core\middlewares;

use core\interfaces\MiddlewareInterface;
use core\library\Auth as CoreLibraryAuth;
use http\Redirect;

class Auth implements MiddlewareInterface
{
    public function execute(): Redirect|null
    {

        if (!isset($_SESSION[ 'auth' ])) {
            return new Redirect('/');
        }

        if (handleLastUpdateSession('auth')) {
            if (!CoreLibraryAuth::isAuth()) {
                return new Redirect('/');
            }
        }

        return handleSessionTimeout();
    }

}
