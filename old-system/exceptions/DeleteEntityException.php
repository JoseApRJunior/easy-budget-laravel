<?php

namespace exceptions;

class DeleteEntityException extends EntityException
{
    public function __construct( \Exception $previous = null )
    {
        $message = "Failed to delete entity  in class '{$this->className}'.";
        parent::__construct( $message, 0, $previous );
    }

}
