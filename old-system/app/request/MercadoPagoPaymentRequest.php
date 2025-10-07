<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class MercadoPagoPaymentRequest extends AbstractFormRequest
{
    protected function execute(): bool
    {
        $validate = new Validator();
        $validate->addRule(new Key('planSlug', Validator::notEmpty()->setTemplate('Field required')));

        return $this->isValidated($validate);
    }

}
