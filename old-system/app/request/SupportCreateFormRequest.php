<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class SupportCreateFormRequest extends AbstractFormRequest
{
    protected function execute(): bool
    {
        $validate = new Validator();
        $validate->addRule(new Key(
            'first_name',
            Validator::stringType()
                ->length(2, 50)
                ->setTemplate('Nome deve ter entre 2 e 50 caracteres'),
        ));
        $validate->addRule(new Key(
            'email',
            Validator::email()
                ->length(null, 255)
                ->setTemplate('Email inválido'),
        ));
        $validate->addRule(new Key(
            'message',
            Validator::stringType()
                ->length(10, 1000)
                ->setTemplate('Mensagem deve ter entre 10 e 1000 caracteres'),
        ));

        return $this->isValidated($validate);
    }

}
