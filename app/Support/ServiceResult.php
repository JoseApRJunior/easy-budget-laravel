<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\OperationStatus;
use Exception;

/**
 * Classe para encapsular resultados de operações de service
 *
 * Esta classe é usada por todos os services para retornar resultados
 * padronizados com status, mensagem e dados, permitindo tratamento
 * consistente de sucessos e erros em toda a aplicação
 */
class ServiceResult
{
    /**
     * Status da operação
     *
     * @var OperationStatus
     */
    private OperationStatus $status;

    /**
     * Mensagem da operação
     *
     * @var string
     */
    private string $message;

    /**
     * Dados da operação
     *
     * @var mixed
     */
    private mixed $data;

    /**
     * Erro da operação (se aplicável)
     *
     * @var Exception|null
     */
    private ?Exception $error = null;

    /**
     * Construtor privado para garantir uso através de métodos estáticos
     *
     * @param OperationStatus $status Status da operação
     * @param string $message Mensagem da operação
     * @param mixed $data Dados da operação
     * @param Exception|null $error Erro (se aplicável)
     */
    private function __construct( OperationStatus $status, string $message, mixed $data = null, ?Exception $error = null )
    {
        $this->status  = $status;
        $this->message = $message;
        $this->data    = $data;
        $this->error   = $error;
    }

    /**
     * Cria um ServiceResult de sucesso
     *
     * @param mixed $data Dados da operação
     * @param string $message Mensagem personalizada (opcional)
     * @return self
     */
    public static function success( mixed $data = null, string $message = '' ): self
    {
        return new self(
            OperationStatus::SUCCESS,
            $message ?: OperationStatus::SUCCESS->getMessage(),
            $data,
        );
    }

    /**
     * Cria um ServiceResult de erro
     *
     * @param OperationStatus|string $statusOrMessage Status ou mensagem de erro
     * @param string $message Mensagem de erro (se $statusOrMessage for OperationStatus)
     * @param mixed $data Dados adicionais
     * @param Exception|null $exception Exceção original
     * @return self
     */
    public static function error( OperationStatus|string $statusOrMessage, string $message = '', mixed $data = null, ?Exception $exception = null ): self
    {
        if ( $statusOrMessage instanceof OperationStatus ) {
            $status = $statusOrMessage;
            $msg    = $message ?: $status->getMessage();
        } else {
            $status = OperationStatus::ERROR;
            $msg    = $statusOrMessage;
        }

        return new self(
            $status,
            $msg,
            $data,
            $exception,
        );
    }

    /**
     * Cria um ServiceResult para recurso não encontrado
     *
     * @param string $resource Nome do recurso não encontrado
     * @param mixed $data Dados adicionais
     * @return self
     */
    public static function notFound( string $resource, mixed $data = null ): self
    {
        return new self(
            OperationStatus::NOT_FOUND,
            OperationStatus::NOT_FOUND->getMessage(),
            $data,
        );
    }

    /**
     * Cria um ServiceResult para dados inválidos
     *
     * @param string $message Mensagem de erro de validação
     * @param array $data Dados de erro de validação
     * @return self
     */
    public static function invalidData( string $message, array $data = [] ): self
    {
        return new self(
            OperationStatus::INVALID_DATA,
            $message,
            $data,
        );
    }

    /**
     * Cria um ServiceResult para acesso proibido
     *
     * @param string $message Mensagem de erro de permissão
     * @param mixed $data Dados adicionais
     * @return self
     */
    public static function forbidden( string $message, mixed $data = null ): self
    {
        return new self(
            OperationStatus::FORBIDDEN,
            $message,
            $data,
        );
    }

    /**
     * Cria um ServiceResult para operação não suportada
     *
     * @param string $message Mensagem de erro
     * @param mixed $data Dados adicionais
     * @return self
     */
    public static function notSupported( string $message, mixed $data = null ): self
    {
        return new self(
            OperationStatus::NOT_SUPPORTED,
            $message,
            $data,
        );
    }

    /**
     * Cria um ServiceResult para usuário não autorizado
     *
     * @param string $message Mensagem de erro
     * @param mixed $data Dados adicionais
     * @return self
     */
    public static function unauthorized( string $message, mixed $data = null ): self
    {
        return new self(
            OperationStatus::UNAUTHORIZED,
            $message,
            $data,
        );
    }

    /**
     * Cria um ServiceResult para recurso expirado
     *
     * @param string $message Mensagem de erro
     * @param mixed $data Dados adicionais
     * @return self
     */
    public static function expired( string $message, mixed $data = null ): self
    {
        return new self(
            OperationStatus::EXPIRED,
            $message,
            $data,
        );
    }

    /**
     * Cria um ServiceResult para recurso bloqueado
     *
     * @param string $message Mensagem de erro
     * @param mixed $data Dados adicionais
     * @return self
     */
    public static function blocked( string $message, mixed $data = null ): self
    {
        return new self(
            OperationStatus::BLOCKED,
            $message,
            $data,
        );
    }

    /**
     * Cria um ServiceResult para operação pendente
     *
     * @param string $message Mensagem de status pendente
     * @param mixed $data Dados adicionais
     * @return self
     */
    public static function pending( string $message, mixed $data = null ): self
    {
        return new self(
            OperationStatus::PENDING,
            $message,
            $data,
        );
    }

    /**
     * Cria um ServiceResult para limite de taxa excedido
     *
     * @param string $message Mensagem de erro
     * @param mixed $data Dados adicionais
     * @return self
     */
    public static function rateLimited( string $message, mixed $data = null ): self
    {
        return new self(
            OperationStatus::RATE_LIMITED,
            $message,
            $data,
        );
    }

    /**
     * Cria um ServiceResult para timeout
     *
     * @param string $message Mensagem de erro
     * @param mixed $data Dados adicionais
     * @return self
     */
    public static function timeout( string $message, mixed $data = null ): self
    {
        return new self(
            OperationStatus::TIMEOUT,
            $message,
            $data,
        );
    }

    /**
     * Cria um ServiceResult para conflito de dados
     *
     * @param string $message Mensagem de erro
     * @param mixed $data Dados adicionais
     * @return self
     */
    public static function conflict( string $message, mixed $data = null ): self
    {
        return new self(
            OperationStatus::CONFLICT,
            $message,
            $data,
        );
    }

    /**
     * Cria um ServiceResult para erro de validação
     *
     * @param string $message Mensagem de erro de validação
     * @param array $data Dados de erro de validação
     * @return self
     */
    public static function validation( string $message, array $data = [] ): self
    {
        return new self(
            OperationStatus::VALIDATION,
            $message,
            $data,
        );
    }

    /**
     * Verifica se a operação foi bem-sucedida
     *
     * @return bool True se foi sucesso, false caso contrário
     */
    public function isSuccess(): bool
    {
        return $this->status->isSuccess();
    }

    /**
     * Verifica se a operação resultou em erro
     *
     * @return bool True se foi erro, false caso contrário
     */
    public function isError(): bool
    {
        return !$this->isSuccess();
    }

    /**
     * Retorna o status da operação
     *
     * @return OperationStatus
     */
    public function getStatus(): OperationStatus
    {
        return $this->status;
    }

    /**
     * Retorna a mensagem da operação
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Retorna os dados da operação
     *
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Retorna o erro da operação (se houver)
     *
     * @return Exception|null
     */
    public function getError(): ?Exception
    {
        return $this->error;
    }

    /**
     * Converte o ServiceResult para array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->isSuccess(),
            'status'  => $this->status->value,
            'message' => $this->message,
            'data'    => $this->data,
            'error'   => $this->error ? $this->error->getMessage() : null
        ];
    }

    /**
     * Converte o ServiceResult para JSON
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode( $this->toArray() );
    }

}
