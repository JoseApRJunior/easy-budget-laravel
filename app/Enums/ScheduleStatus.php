<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Interfaces\StatusEnumInterface;
use App\Traits\Enums\HasStatusEnumMethods;

/**
 * Enum para os status de agendamento
 */
enum ScheduleStatus: string implements StatusEnumInterface
{
    use HasStatusEnumMethods;

    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    /**
     * Retorna o label do status
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::CONFIRMED => 'Confirmado',
            self::COMPLETED => 'Concluído',
            self::CANCELLED => 'Cancelado',
            self::NO_SHOW => 'Não Compareceu',
        };
    }

    /**
     * Retorna a descrição detalhada
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'Agendamento pendente de confirmação',
            self::CONFIRMED => 'Agendamento confirmado',
            self::COMPLETED => 'Agendamento concluído',
            self::CANCELLED => 'Agendamento cancelado',
            self::NO_SHOW => 'Cliente não compareceu',
        };
    }

    /**
     * Retorna a cor associada (Tailwind/Bootstrap context)
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'secondary',
        };
    }

    /**
     * Retorna a cor Hexadecimal
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => '#ffc107',
            self::CONFIRMED => '#007bff',
            self::COMPLETED => '#28a745',
            self::CANCELLED => '#dc3545',
            self::NO_SHOW => '#6c757d',
        };
    }

    /**
     * Retorna o ícone associado (sem prefixo bi-)
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'hourglass-split',
            self::CONFIRMED => 'check-circle-fill',
            self::COMPLETED => 'check-circle-fill',
            self::CANCELLED => 'x-circle-fill',
            self::NO_SHOW => 'slash-circle',
        };
    }

    /**
     * Verifica se o status indica atividade
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::PENDING, self::CONFIRMED => true,
            self::COMPLETED, self::CANCELLED, self::NO_SHOW => false,
        };
    }

    /**
     * Verifica se o status indica finalização
     */
    public function isFinished(): bool
    {
        return !$this->isActive();
    }
}
