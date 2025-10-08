<?php

namespace core\middlewares;

use core\interfaces\MiddlewareInterface;

class Guest implements MiddlewareInterface
{
    public function execute()
    {
        if (handleLastUpdateSession('guest')) {
            var_dump('Guest middleware executed');
        }
    }

}
