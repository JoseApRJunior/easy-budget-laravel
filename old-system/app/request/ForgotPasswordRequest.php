<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class ForgotPasswordRequest extends AbstractFormRequest
{

    protected function execute()
    {
        $validate = new Validator;
        $validate->addRule(
            new Key( 'email', new AllOf(
                Validator::email()->setTemplate( 'Field must be a valid email address' ),
                Validator::notEmpty()->setTemplate( 'Field cannot be empty' ),
            ) ),
        );

        return $this->isValidated( $validate );
    }

}
