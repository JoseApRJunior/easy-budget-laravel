<?php

namespace App\Enums;

enum ServiceStatusEnum: string
{
    case SCHEDULED           = 'scheduled';
    case PREPARING           = 'preparing';
    case ON_HOLD             = 'on-hold';
    case IN_PROGRESS         = 'in-progress';
    case PARTIALLY_COMPLETED = 'partially-completed';
    case APPROVED            = 'approved';
    case REJECTED            = 'rejected';
    case COMPLETED           = 'completed';
    case CANCELLED           = 'cancelled';

    public function getName(): string
    {
        return match ( $this ) {
            self::SCHEDULED           => 'Agendado',
            self::PREPARING           => 'Em Preparação',
            self::ON_HOLD             => 'Em Espera',
            self::IN_PROGRESS         => 'Em Andamento',
            self::PARTIALLY_COMPLETED => 'Concluído Parcial',
            self::APPROVED            => 'Aprovado',
            self::REJECTED            => 'Rejeitado',
            self::COMPLETED           => 'Concluído',
            self::CANCELLED           => 'Cancelado',
        };
    }

    public function getColor(): string
    {
        return match ( $this ) {
            self::SCHEDULED           => '#3B82F6',
            self::PREPARING           => '#06B6D4',
            self::ON_HOLD             => '#F59E0B',
            self::IN_PROGRESS         => '#6366F1',
            self::PARTIALLY_COMPLETED => '#8B5CF6',
            self::APPROVED            => '#10B981',
            self::REJECTED            => '#DC2626',
            self::COMPLETED           => '#10B981',
            self::CANCELLED           => '#6B7280',
        };
    }

    public function getIcon(): string
    {
        return match ( $this ) {
            self::SCHEDULED           => 'mdi-calendar-clock',
            self::PREPARING           => 'mdi-hammer-wrench',
            self::ON_HOLD             => 'mdi-pause-circle',
            self::IN_PROGRESS         => 'mdi-progress-clock',
            self::PARTIALLY_COMPLETED => 'mdi-progress-check',
            self::APPROVED            => 'mdi-check-circle',
            self::REJECTED            => 'mdi-close-circle',
            self::COMPLETED           => 'mdi-check-circle',
            self::CANCELLED           => 'mdi-cancel',
        };
    }

    public function getOrderIndex(): int
    {
        return match ( $this ) {
            self::SCHEDULED           => 1,
            self::PREPARING           => 2,
            self::ON_HOLD             => 3,
            self::IN_PROGRESS         => 4,
            self::PARTIALLY_COMPLETED => 5,
            self::APPROVED            => 6,
            self::REJECTED            => 7,
            self::COMPLETED           => 8,
            self::CANCELLED           => 9,
        };
    }

    public function isActive(): bool
    {
        return true;
    }

    public static function getAllowedTransitions( string $currentSlug ): array
    {
        return match ( $currentSlug ) {
            'scheduled'           => [ 'preparing', 'on-hold' ],
            'preparing'           => [ 'in-progress', 'on-hold' ],
            'in-progress'         => [ 'partially-completed', 'approved', 'rejected', 'completed', 'on-hold' ],
            'partially-completed' => [ 'completed', 'on-hold' ],
            default               => [],
        };
    }

    public static function getFinalStatuses(): array
    {
        return [ self::COMPLETED->value, self::PARTIALLY_COMPLETED->value, self::APPROVED->value, self::REJECTED->value, self::CANCELLED->value ];
    }

    public static function getInactiveStatuses(): array
    {
        return [ self::CANCELLED->value, self::ON_HOLD->value ];
    }

    public function canEdit(): bool
    {
        return match ( $this ) {
            self::SCHEDULED, self::PREPARING, self::ON_HOLD, self::IN_PROGRESS, self::PARTIALLY_COMPLETED => true,
            self::APPROVED, self::REJECTED, self::COMPLETED, self::CANCELLED                              => false,
        };
    }

}
