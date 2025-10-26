<?php

namespace App\Enums;

enum InvoiceStatusEnum: string
{
    case PENDING   = 'pending';
    case PAID      = 'paid';
    case OVERDUE   = 'overdue';
    case CANCELLED = 'cancelled';

    public function getName(): string
    {
        return match ( $this ) {
            self::PENDING   => 'Pendente',
            self::PAID      => 'Paga',
            self::OVERDUE   => 'Vencida',
            self::CANCELLED => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ( $this ) {
            self::PENDING   => '#F59E0B',
            self::PAID      => '#10B981',
            self::OVERDUE   => '#DC2626',
            self::CANCELLED => '#6B7280',
        };
    }

    public function getIcon(): string
    {
        return match ( $this ) {
            self::PENDING   => 'mdi-timer-sand',
            self::PAID      => 'mdi-cash-check',
            self::OVERDUE   => 'mdi-alert',
            self::CANCELLED => 'mdi-cancel',
        };
    }

    public function getOrderIndex(): int
    {
        return match ( $this ) {
            self::PENDING   => 1,
            self::PAID      => 2,
            self::OVERDUE   => 3,
            self::CANCELLED => 4,
        };
    }

    public function isActive(): bool
    {
        return true;
    }

    public static function getAllowedTransitions( string $currentSlug ): array
    {
        return match ( $currentSlug ) {
            'pending' => [ 'paid', 'overdue', 'cancelled' ],
            'overdue' => [ 'paid', 'cancelled' ],
            default   => [],
        };
    }

    public static function getFinalStatuses(): array
    {
        return [ self::PAID->value, self::CANCELLED->value ];
    }

    public static function getInactiveStatuses(): array
    {
        return [ self::CANCELLED->value ];
    }

}
