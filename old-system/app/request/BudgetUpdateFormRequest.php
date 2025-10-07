<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator as v;

class BudgetUpdateFormRequest extends AbstractFormRequest
{
    protected function execute(): bool
    {
        $validator = v::create();

        $validator->addRule(new Key('customer_id', v::notEmpty()->setTemplate('O Cliente é obrigatório')));
        $validator->addRule(new Key('code', v::optional(v::stringType())));
        $validator->addRule(new Key('customer_name', v::notEmpty()->setTemplate('O Nome é obrigatório')));
        $validator->addRule(new Key('phone', v::notEmpty()->setTemplate('O Telefone é obrigatório')));
        $validator->addRule(new Key('email', new AllOf(
            v::email()->setTemplate('Informe um e-mail válido'),
            v::notEmpty()->setTemplate('O e-mail é obrigatório'),
        )));
        $validator->addRule(new Key('due_date', v::date('Y-m-d')->setTemplate('Data de vencimento inválida')));
        $validator->addRule(new Key('total', v::optional(v::floatVal()->setTemplate('O Valor total é inválido'))));

        $validator->addRule(new Key('description', v::optional(v::stringType())));
        $validator->addRule(new Key('payment_terms', v::optional(v::stringType())));
        $validator->addRule(new Key('attachment', v::optional(v::stringType())));
        $validator->addRule(new Key('history', v::optional(v::stringType())));

        return $this->isValidated($validator);
    }

}
