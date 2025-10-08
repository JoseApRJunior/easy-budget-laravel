<?php

namespace exceptions;

class EntityException extends \Exception
{

    private $className;

    /**
     * Construtor da exceção EntityNotFoundException.
     *
     * @param string $entityName Nome da entidade (tabela) que não foi encontrada.
     * @param mixed $entityParam Parâmetro da entidade que não foi encontrada.
     * @param int $code Código do erro.
     * @param \Exception|null $previous Exceção anterior usada para encadeamento de exceções.
     */
    public function __construct( $message, $code = 0, \Exception $previous = null )
    {
        $message = ", tente mais tarde ou entre em contato com suporte.";

        parent::__construct( $message, $code, $previous );
    }

    /**
     * Obtém o nome da classe que lançou a exceção.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

}
