<?php

namespace app\support;

use app\enums\OperationStatus;

class ServiceResult
{
    public readonly OperationStatus $status;
    public readonly mixed           $data;
    public readonly ?string         $message;

    private function __construct( OperationStatus $status, mixed $data = null, ?string $message = null )
    {
        $this->status  = $status;
        $this->data    = $data;
        $this->message = $message;
    }

    public static function success( mixed $data, ?string $message = null ): self
    {
        return new self( OperationStatus::SUCCESS, $data, $message );
    }

    public static function error( OperationStatus $status, mixed $message ): self
    {
        return new self( $status, null, $message );
    }

    /**
     * Verifica se o resultado representa uma operação bem-sucedida.
     *
     * @return bool True se a operação foi bem-sucedida, false caso contrário
     */
    public function isSuccess(): bool
    {
        return $this->status === OperationStatus::SUCCESS;
    }

    /**
     * Retorna os dados do resultado.
     *
     * @return mixed Os dados do resultado
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Retorna os dados do resultado.
     *
     * @return string|null A mensagem do resultado
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Retorna os erros do resultado.
     *
     * @return array Lista de erros
     */
    public function getErrors(): array
    {
        if ( !$this->isSuccess() && $this->message ) {
            return [ $this->message ];
        }
        return [];
    }

}