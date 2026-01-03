<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum que representa os possíveis status de operação retornados pelos services
 *
 * Este enum é usado em conjunto com a classe ServiceResult para padronizar
 * os retornos de operações nos services da aplicação
 */
enum OperationStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    use \App\Traits\Enums\HasStatusEnumMethods;

    /** Operação executada com sucesso */
    case SUCCESS = 'success';

    /** Recurso não encontrado */
    case NOT_FOUND = 'not_found';

    /** Erro genérico na operação */
    case ERROR = 'error';

    /** Acesso negado/proibido */
    case FORBIDDEN = 'forbidden';

    /** Operação não autorizada */
    case UNAUTHORIZED = 'unauthorized';

    /** Dados inválidos fornecidos */
    case INVALID_DATA = 'invalid_data';

    /** Conflito de dados */
    case CONFLICT = 'conflict';

    /** Erro de validação */
    case VALIDATION_ERROR = 'validation_error';

    /** Token expirado */
    case EXPIRED = 'expired';

    /**
     * Retorna uma descrição para o status
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::SUCCESS => 'Operação executada com sucesso',
            self::NOT_FOUND => 'Recurso não encontrado',
            self::ERROR => 'Erro interno do servidor',
            self::FORBIDDEN => 'Acesso negado',
            self::UNAUTHORIZED => 'Operação não autorizada',
            self::INVALID_DATA => 'Dados inválidos fornecidos',
            self::CONFLICT => 'Conflito de dados',
            self::VALIDATION_ERROR => 'Erro de validação',
            self::EXPIRED => 'Token expirado'
        };
    }

    /**
     * Retorna a cor associada ao status para interface
     *
     * @return string Cor em formato hexadecimal
     */
    public function getColor(): string
    {
        return match ($this) {
            self::SUCCESS => '#28a745', // Verde
            self::NOT_FOUND, self::INVALID_DATA, self::VALIDATION_ERROR, self::EXPIRED => '#ffc107', // Amarelo
            self::ERROR, self::FORBIDDEN, self::UNAUTHORIZED, self::CONFLICT => '#dc3545', // Vermelho
        };
    }

    /**
     * Retorna o ícone associado ao status
     *
     * @return string Nome do ícone para interface
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::SUCCESS => 'bi-check-circle',
            self::NOT_FOUND => 'bi-search',
            self::ERROR => 'bi-exclamation-triangle',
            self::FORBIDDEN => 'bi-shield-x',
            self::UNAUTHORIZED => 'bi-shield-lock',
            self::INVALID_DATA => 'bi-exclamation-circle',
            self::CONFLICT => 'bi-arrow-left-right',
            self::VALIDATION_ERROR => 'bi-exclamation-diamond',
            self::EXPIRED => 'bi-hourglass-bottom',
        };
    }

    /**
     * Verifica se o status indica atividade (operação em andamento)
     *
     * @return bool True se estiver em estado de operação
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::SUCCESS, self::ERROR, self::NOT_FOUND, self::FORBIDDEN, self::UNAUTHORIZED,
            self::INVALID_DATA, self::CONFLICT, self::VALIDATION_ERROR, self::EXPIRED => true,
        };
    }

    /**
     * Verifica se o status indica finalização (operação concluída)
     *
     * @return bool True se estiver finalizado
     */
    public function isFinished(): bool
    {
        return true; // Todos os status de operação são finais
    }

    /**
     * Retorna metadados completos do status
     *
     * @return array<string, mixed> Array com descrição, cor, ícone e flags
     */
    public function getMetadata(): array
    {
        return array_merge($this->defaultMetadata(), [
            'is_success' => $this->isSuccess(),
            'is_error' => $this->isError(),
        ]);
    }

    private function defaultMetadata(): array
    {
        return [
            'value' => $this->value,
            'description' => $this->getDescription(),
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
            'is_active' => $this->isActive(),
            'is_finished' => $this->isFinished(),
        ];
    }

    /**
     * Verifica se o status indica sucesso
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * Verifica se o status indica erro
     */
    public function isError(): bool
    {
        return ! $this->isSuccess();
    }

    /**
     * Retorna uma mensagem padrão para o status (alias para getDescription)
     */
    public function getMessage(): string
    {
        return $this->getDescription();
    }
}
