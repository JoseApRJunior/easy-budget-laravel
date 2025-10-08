<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class UserPreCreateFormRequest extends AbstractFormRequest
{
    protected function execute(): bool
    {
        $validate = new Validator();
        $validate->addRule( new Key( 'firstName', Validator::notEmpty()->setTemplate( 'Field required' ) ) );
        $validate->addRule( new Key( 'lastName', Validator::notEmpty()->setTemplate( 'Field required' ) ) );
        $validate->addRule( new Key(
            'email',
            new AllOf(
                Validator::email()->setTemplate( 'Field email' ),
                Validator::notEmpty()->setTemplate( 'Field required' ),
            ),
        ) );
        $validate->addRule( new Key( 'phone', Validator::notEmpty()->setTemplate( 'Field required' ) ) );
        $validate->addRule( new Key(
            'password',
            new AllOf(
                Validator::notEmpty()->setTemplate( 'Field required' ),
                Validator::length( 6, null )->setTemplate( 'Field password must be at least 6 characters long' ),
            ),
        ) );

        return $this->isValidated( $validate );
    }

}
