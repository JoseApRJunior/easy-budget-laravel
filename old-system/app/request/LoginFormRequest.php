<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class LoginFormRequest extends AbstractFormRequest
{
    protected function execute()
    {
        $validate = new Validator();
        $validate->addRule(
            new Key('email', new AllOf(
                Validator::email()->setTemplate('Field must be a valid email address'),
                Validator::notEmpty()->setTemplate('Field cannot be empty'),
            )),
        );
        $validate->addRule(
            new Key('password', new AllOf(
                Validator::notEmpty()->setTemplate('Field cannot be empty'),
                Validator::length(6, null)->setTemplate('Field must be at least 6 characters long'),
            )),
        );

        return $this->isValidated($validate);
    }

}
