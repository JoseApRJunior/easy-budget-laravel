<?php

namespace exceptions;

class CreateEntityException extends EntityException
{
    public function __construct( \Exception $previous = null )
    {
        $message = "Failed to create entity in class '{$this->getClassName()}'.";
        parent::__construct( $message, 0, $previous );
    }

}
