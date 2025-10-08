<?php

namespace exceptions;

class EntityNotFoundException extends \Exception
{
    private $entityName;
    private $entityParam;
    private $className;

    /**
     * Construtor da exceção EntityNotFoundException.
     *
     * @param string $entityName Nome da entidade (tabela) que não foi encontrada.
     * @param mixed $entityParam Parâmetro da entidade que não foi encontrada.
     * @param int $code Código do erro.
     * @param \Exception|null $previous Exceção anterior usada para encadeamento de exceções.
     */
    public function __construct( $entityName, $entityParam, $code = 0, \Exception $previous = null )
    {
        $this->entityName  = $entityName;
        $this->entityParam = $entityParam;

        // Captura o nome da classe que lançou a exceção
        $trace           = debug_backtrace();
        $this->className = isset( $trace[ 1 ][ 'class' ] ) ? $trace[ 1 ][ 'class' ] : null;

        $message = "Entidade '{$entityName}' com parâmetro de busca '{$entityParam}' não foi encontrada na classe '{$this->className}'.";

        parent::__construct( $message, $code, $previous );
    }

    /**
     * Obtém o nome da entidade (tabela) que não foi encontrada.
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Obtém o parâmetro da entidade que não foi encontrada.
     *
     * @return mixed
     */
    public function getEntityParam()
    {
        return $this->entityParam;
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

// Exemplo de uso:
// try {
//     $plans = $this->plan->findActivePlans();

// } catch ( EntityNotFoundException $e ) {
//     echo $e->getMessage(); (); // Exemplo de saída: "Entidade 'users' com parâmetro de busca 'email@example.com' não foi encontrada na classe 'SomeRepository'."
// }

// Exemplo de captura de exceção:

// if ( !$entity ) {
//     throw new EntityNotFoundException( $this->table, $criteria );
// }
