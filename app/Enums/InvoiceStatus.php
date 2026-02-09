<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    use \App\Traits\Enums\HasStatusEnumMethods {
        getMetadata as defaultMetadata;
    }

    /** Fatura pendente de pagamento */
    case PENDING = 'pending';

    /** Fatura paga */
    case PAID = 'paid';

    /** Fatura cancelada */
    case CANCELLED = 'cancelled';

    /** Fatura vencida */
    case OVERDUE = 'overdue';

    /**
     * Retorna o label do status
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::PAID => 'Pago',
            self::CANCELLED => 'Cancelado',
            self::OVERDUE => 'Vencido',
        };
    }

    /**
     * Retorna a descrição detalhada
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'Fatura pendente de pagamento',
            self::PAID => 'Fatura paga',
            self::CANCELLED => 'Fatura cancelada',
            self::OVERDUE => 'Fatura vencida',
        };
    }

    /**
     * Retorna a cor associada (Tailwind classes ou Hex)
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::CANCELLED => 'danger',
            self::OVERDUE => 'secondary',
        };
    }

    /**
     * Retorna a cor Hexadecimal (Sincronizado com theme.php)
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => config('theme.colors.warning', '#d97706'),
            self::PAID => config('theme.colors.success', '#059669'),
            self::CANCELLED => config('theme.colors.error', '#dc2626'),
            self::OVERDUE => config('theme.colors.secondary', '#94a3b8'),
        };
    }

    /**
     * Retorna o ícone associado
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'hourglass-split',
            self::PAID => 'check-circle-fill',
            self::CANCELLED => 'x-circle-fill',
            self::OVERDUE => 'calendar-x-fill',
        };
    }

    /**
     * Verifica se o status indica que a fatura está pendente
     */
    public function isPending(): bool
    {
        return match ($this) {
            self::PENDING, self::OVERDUE => true,
            self::PAID, self::CANCELLED => false,
        };
    }

    /**
     * Verifica se o status indica atividade
     */
    public function isActive(): bool
    {
        return $this->isPending();
    }

    /**
     * Verifica se o status indica que a fatura foi finalizada
     */
    public function isFinished(): bool
    {
        return match ($this) {
            self::PAID, self::CANCELLED => true,
            self::PENDING, self::OVERDUE => false,
        };
    }

    /**
     * Verifica se o status indica que a fatura pode ser cobrada
     */
    public function isChargeable(): bool
    {
        return match ($this) {
            self::PENDING => true,
            default => false,
        };
    }

    /**
     * Verifica se é possível transitar para um determinado status
     */
    public function canTransitionTo(self $targetStatus): bool
    {
        $validTransitions = [
            self::PENDING->value => [self::PAID->value, self::CANCELLED->value, self::OVERDUE->value],
            self::PAID->value => [],
            self::CANCELLED->value => [],
            self::OVERDUE->value => [self::PAID->value, self::CANCELLED->value],
        ];

        return in_array($targetStatus->value, $validTransitions[$this->value] ?? []);
    }

    /**
     * Retorna a ordem de prioridade para exibição
     */
    public function getPriorityOrder(): int
    {
        return match ($this) {
            self::OVERDUE => 1,
            self::PENDING => 2,
            self::PAID => 3,
            self::CANCELLED => 4,
        };
    }

    /**
     * Retorna metadados completos do status
     */
    public function getMetadata(): array
    {
        return array_merge($this->defaultMetadata(), [
            'priority_order' => $this->getPriorityOrder(),
            'is_chargeable' => $this->isChargeable(),
        ]);
    }
}
