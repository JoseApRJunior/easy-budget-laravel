<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class UserCreateFormRequest extends AbstractFormRequest
{
    protected function execute(): bool
    {
        $validate = new Validator();
        $validate->addRule(new Key('first_name', Validator::notEmpty()->setTemplate('Field required')));
        $validate->addRule(new Key('last_name', Validator::notEmpty()->setTemplate('Field required')));
        $validate->addRule(new Key(
            'email',
            new AllOf(
                Validator::email()->setTemplate('Field email'),
                Validator::notEmpty()->setTemplate('Field required'),
            ),
        ));
        $validate->addRule(new Key('phone', Validator::notEmpty()->setTemplate('Field required')));
        $validate->addRule(new Key(
            'password',
            new AllOf(
                Validator::notEmpty()->setTemplate('Field required'),
                Validator::length(6, null)->setTemplate('Field must be at least 6 characters long'),
                Validator::regex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$!%*?&])[A-Za-z\d@#$!%*?&]{6,}$/')->setTemplate('Field must be strong'),
            ),
        ));
        $validate->addRule(new Key(
            'terms_accepted',
            Validator::equals('on')->setTemplate('Field must be accepted'),
        ));

        return $this->isValidated($validate);
    }

}
