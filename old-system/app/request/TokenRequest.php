<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class TokenRequest extends AbstractFormRequest
{
    protected function execute()
    {
        $validate = new Validator();
        $validate->addRule(
            new Key(
                'token',
                new AllOf(
                    Validator::notEmpty()->setTemplate('O token não pode estar vazio'),
                    Validator::stringType()->length(32)->regex('/^[a-fA-F0-9]+$/')->setTemplate('O token deve ser uma string hexadecimal 32 caracteres'),
                ),
            ),
        );

        return $this->isValidated($validate);
    }

}
