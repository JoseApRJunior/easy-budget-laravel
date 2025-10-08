<?php

namespace app\controllers;

use app\interfaces\ControllerInterface;
use core\library\Session;
use http\Request;

abstract class AbstractController implements ControllerInterface
{
    protected $authenticated = null;

    public function __construct(protected Request $request)
    {
        if (Session::has('auth')) {
            $this->authenticated = Session::get('auth');
        }
    }

}
