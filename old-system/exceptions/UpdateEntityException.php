<?php

namespace exceptions;

class UpdateEntityException extends EntityException
{
    public function __construct( \Exception $previous = null )
    {
        $message = "Failed to update entity in class '{$this->className}'.";
        parent::__construct( $message, 0, $previous );
    }

}
