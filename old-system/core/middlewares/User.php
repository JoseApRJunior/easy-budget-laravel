<?php

namespace core\middlewares;

use core\interfaces\MiddlewareInterface;

class User implements MiddlewareInterface
{
    public function execute()
    {
        if (handleLastUpdateSession('user')) {
            var_dump('User middleware executed');
        }
    }

}
