<?php

namespace exceptions;

class ReadEntityException extends EntityException
{
    public function __construct( $entityName, \Exception $previous = null )
    {
        $message = "Failed to read in  ";
        parent::__construct( $message, $entityName, 0, $previous );
    }

}
