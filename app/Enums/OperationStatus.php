<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum que representa os possíveis status de operação retornados pelos services
 *
 * Este enum é usado em conjunto com a classe ServiceResult para padronizar
 * os retornos de operações nos services da aplicação
 */
enum OperationStatus: string
{
    /** Operação executada com sucesso */
    case SUCCESS = 'success';

    /** Recurso não encontrado */
    case NOT_FOUND = 'not_found';

    /** Erro genérico na operação */
    case ERROR = 'error';

    /** Acesso negado/proibido */
    case FORBIDDEN = 'forbidden';

    /** Dados inválidos fornecidos */
    case INVALID_DATA = 'invalid_data';

    /**   Conflito de dados   */
    case CONFLICT = 'conflict';

    /**
     * Verifica se o status indica sucesso
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * Verifica se o status indica erro
     *
     * @return bool
     */
    public function isError(): bool
    {
        return !$this->isSuccess();
    }

    /**
     * Retorna uma mensagem padrão para o status
     *
     * @return string
     */
    public function getMessage(): string
    {
        return match ( $this ) {
            self::SUCCESS      => 'Operação realizada com sucesso',
            self::NOT_FOUND    => 'Recurso não encontrado',
            self::ERROR        => 'Erro interno do servidor',
            self::FORBIDDEN    => 'Acesso negado',
            self::INVALID_DATA => 'Dados inválidos',
            self::CONFLICT     => 'Conflito de dados'
        };
    }

}
