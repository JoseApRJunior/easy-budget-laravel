<?php

namespace exceptions;

class ValidationException extends \Exception
{
    private $field;
    private $validationError;

    public function __construct($field, $validationError, $code = 0, \Exception $previous = null)
    {
        $this->field = $field;
        $this->validationError = $validationError;
        $message = "Erro de validação no campo '{$field}': {$validationError}.";
        parent::__construct($message, $code, $previous);
    }

    public function getField()
    {
        return $this->field;
    }

    public function getValidationError()
    {
        return $this->validationError;
    }

}
