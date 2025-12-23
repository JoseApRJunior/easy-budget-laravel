<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    /** Pagamento pendente */
    case PENDING = 'PENDING';

    /** Pagamento em processamento */
    case PROCESSING = 'PROCESSING';

    /** Pagamento concluído */
    case COMPLETED = 'COMPLETED';

    /** Pagamento falhou */
    case FAILED = 'FAILED';

    /** Pagamento estornado */
    case REFUNDED = 'REFUNDED';

    /**
     * Retorna uma descrição para cada status.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'Pagamento pendente',
            self::PROCESSING => 'Processando pagamento',
            self::COMPLETED => 'Pagamento concluído',
            self::FAILED => 'Pagamento falhou',
            self::REFUNDED => 'Pagamento estornado',
        };
    }

    /**
     * Retorna a cor associada a cada status.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => '#ffc107', // Amarelo
            self::PROCESSING => '#17a2b8', // Azul
            self::COMPLETED => '#28a745', // Verde
            self::FAILED => '#dc3545', // Vermelho
            self::REFUNDED => '#6c757d', // Cinza
        };
    }

    /**
     * Retorna o ícone associado a cada status.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'bi-clock',
            self::PROCESSING => 'bi-arrow-repeat',
            self::COMPLETED => 'bi-check-circle-fill',
            self::FAILED => 'bi-x-circle-fill',
            self::REFUNDED => 'bi-arrow-counterclockwise',
        };
    }

    /**
     * Verifica se o status indica que o pagamento está ativo.
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::PENDING, self::PROCESSING => true,
            self::COMPLETED, self::FAILED, self::REFUNDED => false,
        };
    }

    /**
     * Verifica se o status indica que o pagamento foi finalizado.
     */
    public function isFinished(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FAILED, self::REFUNDED => true,
            self::PENDING, self::PROCESSING => false,
        };
    }

    /**
     * Verifica se o pagamento foi bem-sucedido.
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Retorna transições válidas para cada status.
     */
    public function getValidTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::PROCESSING, self::FAILED],
            self::PROCESSING => [self::COMPLETED, self::FAILED],
            self::COMPLETED => [self::REFUNDED],
            self::FAILED => [self::PENDING], // Retry
            self::REFUNDED => [], // Final state
        };
    }

    /**
     * Verifica se pode transitar para um status específico.
     */
    public function canTransitionTo(PaymentStatus $targetStatus): bool
    {
        return in_array($targetStatus, $this->getValidTransitions());
    }

    /**
     * Retorna todos os status disponíveis.
     */
    public static function getAll(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::COMPLETED,
            self::FAILED,
            self::REFUNDED,
        ];
    }

    /**
     * Retorna apenas os status ativos.
     */
    public static function getActive(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
        ];
    }

    /**
     * Retorna apenas os status finalizados.
     */
    public static function getFinished(): array
    {
        return [
            self::COMPLETED,
            self::FAILED,
            self::REFUNDED,
        ];
    }

    /**
     * Retorna opções formatadas para formulários.
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $status) {
            $options[$status->value] = $status->getDescription();
        }
        return $options;
    }

    /**
     * Cria instância do enum a partir de string.
     */
    public static function fromString(string $value): ?PaymentStatus
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        return null;
    }
}