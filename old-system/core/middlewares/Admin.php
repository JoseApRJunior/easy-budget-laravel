<?php

namespace core\middlewares;

use core\interfaces\MiddlewareInterface;
use core\library\Auth as CoreLibraryAuth;
use http\Redirect;

class Admin implements MiddlewareInterface
{
    public function execute(): Redirect|null
    {
        if (handleLastUpdateSession('admin')) {
            if (!CoreLibraryAuth::isAdmin()) {
                return new Redirect('/');
            }
        }

        return null;
    }

}
