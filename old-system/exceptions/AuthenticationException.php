<?php

namespace exceptions;

class AuthenticationException extends \Exception
{
    public function __construct($message = "Erro de autenticação", $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
