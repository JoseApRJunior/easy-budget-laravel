<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator as v;

class ServiceFormRequest extends AbstractFormRequest
{
    protected function execute(): bool
    {
        $validator = v::create();

        $validator->addRule(new Key('code', v::notEmpty()->setTemplate('O campo é obrigatório')));
        $validator->addRule(new Key('category', v::notEmpty()->setTemplate(' O campo é obrigatório')));
        $validator->addRule(new Key('due_date', v::date('Y-m-d')->setTemplate('A data é inválida')));
        $validator->addRule(new Key('description', v::optional(v::stringType())));
        $validator->addRule(new Key('items', v::optional(v::stringType())));

        return $this->isValidated($validator);
    }

}
