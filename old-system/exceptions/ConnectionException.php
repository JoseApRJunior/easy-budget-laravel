<?php

namespace exceptions;

class ConnectionException extends \Exception
{
    private $serviceName;

    public function __construct($serviceName, $message = "Falha na conexão", $code = 0, \Exception $previous = null)
    {
        $this->serviceName = $serviceName;
        $message = "Falha na conexão com o serviço '{$serviceName}': {$message}.";
        parent::__construct($message, $code, $previous);
    }

    public function getServiceName()
    {
        return $this->serviceName;
    }

}
