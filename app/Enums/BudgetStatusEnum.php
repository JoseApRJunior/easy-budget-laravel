<?php

namespace App\Enums;

enum BudgetStatusEnum: string
{
    case DRAFT     = 'draft';
    case SENT      = 'sent';
    case APPROVED  = 'approved';
    case COMPLETED = 'completed';
    case REJECTED  = 'rejected';
    case EXPIRED   = 'expired';
    case REVISED   = 'revised';
    case CANCELLED = 'cancelled';

    public function getName(): string
    {
        return match ( $this ) {
            self::DRAFT     => 'Rascunho',
            self::SENT      => 'Enviado',
            self::APPROVED  => 'Aprovado',
            self::COMPLETED => 'Concluído',
            self::REJECTED  => 'Rejeitado',
            self::EXPIRED   => 'Expirado',
            self::REVISED   => 'Revisado',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function getColor(): string
    {
        return match ( $this ) {
            self::DRAFT     => '#9CA3AF',
            self::SENT      => '#3B82F6',
            self::APPROVED  => '#10B981',
            self::COMPLETED => '#059669',
            self::REJECTED  => '#EF4444',
            self::EXPIRED   => '#F59E0B',
            self::REVISED   => '#8B5CF6',
            self::CANCELLED => '#6B7280',
        };
    }

    public function getIcon(): string
    {
        return match ( $this ) {
            self::DRAFT     => 'mdi-file-document-edit',
            self::SENT      => 'mdi-send',
            self::APPROVED  => 'mdi-check-circle',
            self::COMPLETED => 'mdi-check-circle-outline',
            self::REJECTED  => 'mdi-close-circle',
            self::EXPIRED   => 'mdi-timer-off',
            self::REVISED   => 'mdi-file-compare',
            self::CANCELLED => 'mdi-cancel',
        };
    }

    public function getOrderIndex(): int
    {
        return match ( $this ) {
            self::DRAFT     => 1,
            self::SENT      => 2,
            self::APPROVED  => 3,
            self::COMPLETED => 4,
            self::REJECTED  => 5,
            self::EXPIRED   => 6,
            self::REVISED   => 8,
            self::CANCELLED => 7,
        };
    }

    public function isActive(): bool
    {
        return true;
    }

    public static function getAllowedTransitions( string $currentSlug ): array
    {
        return match ( $currentSlug ) {
            'draft'    => [ 'sent' ],
            'sent'     => [ 'approved', 'rejected', 'revised' ],
            'approved' => [ 'completed' ],
            default    => [],
        };
    }

    public static function getFinalStatuses(): array
    {
        return [ self::COMPLETED->value, self::REJECTED->value, self::EXPIRED->value, self::CANCELLED->value ];
    }

    public static function getInactiveStatuses(): array
    {
        return [ self::COMPLETED->value, self::CANCELLED->value, self::REJECTED->value, self::EXPIRED->value ];
    }

}
