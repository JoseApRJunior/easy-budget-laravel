<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class ProviderUpdatePasswordFormRequest extends AbstractFormRequest
{
    protected function execute()
    {
        $validate = new Validator();
        $validate->addRule(new Key(
            'current_password',
            new AllOf(
                Validator::notEmpty()->setTemplate('Field required'),
                Validator::length(6, null)->setTemplate('Field password must be at least 6 characters long'),
            ),
        ));

        $validate->addRule(new Key(
            'password',
            new AllOf(
                Validator::notEmpty()->setTemplate('Field required'),
                Validator::length(6, null)->setTemplate('Field password must be at least 6 characters long'),
                Validator::regex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$!%*?&])[A-Za-z\d@#$!%*?&]{6,}$/')->setTemplate('Field password must be strong'),
            ),
        ));

        return $this->isValidated($validate);
    }

}
