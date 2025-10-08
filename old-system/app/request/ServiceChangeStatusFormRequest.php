<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator as v;

class ServiceChangeStatusFormRequest extends AbstractFormRequest
{
    protected function execute(): bool
    {
        $validator = v::create();

        $validator->addRule(new Key('service_id', v::notEmpty()));
        $validator->addRule(new Key('service_code', v::notEmpty()));
        $validator->addRule(new Key('current_status_id', v::notEmpty()));
        $validator->addRule(new Key('current_status_name', v::notEmpty()));
        $validator->addRule(new Key('current_status_slug', v::notEmpty()));
        $validator->addRule(new Key('action', v::notEmpty()));
        $validator->addRule(new Key('new_due_date', v::optional(v::stringType())));
        $validator->addRule(new Key('start_date_time', v::optional(v::stringType())));
        $validator->addRule(new Key('location', v::optional(v::stringType())));

        return $this->isValidated($validator);
    }

}
