<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator as v;

class BudgetChangeStatusFormRequest extends AbstractFormRequest
{
    protected function execute(): bool
    {
        $validator = v::create();

        $validator->addRule(new Key('budget_id', v::notEmpty()));
        $validator->addRule(new Key('budget_code', v::notEmpty()));
        $validator->addRule(new Key('current_status_id', v::notEmpty()));
        $validator->addRule(new Key('current_status_name', v::notEmpty()));
        $validator->addRule(new Key('current_status_slug', v::notEmpty()));
        $validator->addRule(new Key('action', v::notEmpty()));

        return $this->isValidated($validator);
    }

}
