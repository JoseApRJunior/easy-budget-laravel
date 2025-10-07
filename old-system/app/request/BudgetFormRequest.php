<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator as v;

class BudgetFormRequest extends AbstractFormRequest
{
    protected function execute(): bool
    {
        $validator = v::create();

        $validator->addRule(new Key('customer_id', v::notEmpty()->setTemplate('O Cliente é obrigatório')));
        $validator->addRule(new Key('due_date', v::date('Y-m-d')->setTemplate('Data de vencimento inválida')));
        $validator->addRule(new Key('description', v::optional(v::stringType())));
        $validator->addRule(new Key('payment_terms', v::optional(v::stringType())));

        return $this->isValidated($validator);
    }

}
