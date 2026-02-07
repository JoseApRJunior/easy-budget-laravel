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
     */
    private OperationStatus $status;

    /**
     * Mensagem da operação
     */
    private string $message;

    /**
     * Dados da operação
     */
    private mixed $data;

    /**
     * Erro da operação (se aplicável)
     */
    private ?Exception $error = null;

    /**
     * Construtor privado para garantir uso através de métodos estáticos
     *
     * @param  OperationStatus  $status  Status da operação
     * @param  string  $message  Mensagem da operação
     * @param  mixed  $data  Dados da operação
     * @param  Exception|null  $error  Erro (se aplicável)
     */
    private function __construct(OperationStatus $status, string $message, mixed $data = null, ?Exception $error = null)
    {
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
        $this->error = $error;
    }

    /**
     * Cria um ServiceResult de sucesso
     *
     * @param  mixed  $data  Dados da operação
     * @param  string  $message  Mensagem personalizada (opcional)
     */
    public static function success(mixed $data = null, string $message = ''): self
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
     * @param  OperationStatus|string  $statusOrMessage  Status ou mensagem de erro
     * @param  string  $message  Mensagem de erro (se $statusOrMessage for OperationStatus)
     * @param  mixed  $data  Dados adicionais
     * @param  Exception|null  $exception  Exceção original
     */
    public static function error(OperationStatus|string $statusOrMessage, string $message = '', mixed $data = null, ?Exception $exception = null): self
    {
        if ($statusOrMessage instanceof OperationStatus) {
            $status = $statusOrMessage;
            $msg = $message ?: $status->getMessage();
        } else {
            $status = OperationStatus::ERROR;
            $msg = $statusOrMessage;
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
     * @param  string  $resource  Nome do recurso não encontrado
     * @param  mixed  $data  Dados adicionais
     */
    public static function notFound(string $resource, mixed $data = null): self
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
     * @param  string  $message  Mensagem de erro de validação
     * @param  array  $data  Dados de erro de validação
     */
    public static function invalidData(string $message, array $data = []): self
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
     * @param  string  $message  Mensagem de erro de permissão
     * @param  mixed  $data  Dados adicionais
     */
    public static function forbidden(string $message, mixed $data = null): self
    {
        return new self(
            OperationStatus::FORBIDDEN,
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
        return ! $this->isSuccess();
    }

    /**
     * Retorna o status da operação
     */
    public function getStatus(): OperationStatus
    {
        return $this->status;
    }

    /**
     * Retorna a mensagem da operação
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Retorna os dados da operação
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Retorna o erro da operação (se houver)
     */
    public function getError(): ?Exception
    {
        return $this->error;
    }

    /**
     * Retorna os erros da operação (detalhes de validação, etc.)
     */
    public function getErrors(): mixed
    {
        return $this->isError() ? $this->data : null;
    }

    /**
     * Converte o ServiceResult para array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->isSuccess(),
            'status' => $this->status->value,
            'message' => $this->message,
            'data' => $this->data,
            'error' => $this->error ? $this->error->getMessage() : null,
        ];
    }

    /**
     * Converte o ServiceResult para JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
