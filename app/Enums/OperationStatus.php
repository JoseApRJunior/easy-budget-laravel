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

    /** Operação não suportada */
    case NOT_SUPPORTED = 'not_supported';

    /** Usuário não autenticado */
    case UNAUTHORIZED = 'unauthorized';

    /** Token ou recurso expirado */
    case EXPIRED = 'expired';

    /** Recurso bloqueado */
    case BLOCKED = 'blocked';

    /** Operação pendente */
    case PENDING = 'pending';

    /** Limite de taxa excedido */
    case RATE_LIMITED = 'rate_limited';

    /** Timeout na operação */
    case TIMEOUT = 'timeout';

    /** Conflito de dados */
    case CONFLICT = 'conflict';

    /** Erro de validação */
    case VALIDATION = 'validation';

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
            self::SUCCESS       => 'Operação realizada com sucesso',
            self::NOT_FOUND     => 'Recurso não encontrado',
            self::ERROR         => 'Erro interno do servidor',
            self::FORBIDDEN     => 'Acesso negado',
            self::INVALID_DATA  => 'Dados inválidos',
            self::NOT_SUPPORTED => 'Operação não suportada',
            self::UNAUTHORIZED  => 'Usuário não autorizado',
            self::EXPIRED       => 'Recurso expirado',
            self::BLOCKED       => 'Recurso bloqueado',
            self::PENDING       => 'Operação pendente',
            self::RATE_LIMITED  => 'Limite de requisições excedido',
            self::TIMEOUT       => 'Timeout na operação',
            self::CONFLICT      => 'Conflito de dados',
            self::VALIDATION    => 'Erro de validação',
        };
    }

}
