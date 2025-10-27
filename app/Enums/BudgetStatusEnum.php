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

    // Propriedades públicas para compatibilidade com views
    public const NAMES = [
        'draft'     => 'Rascunho',
        'sent'      => 'Enviado',
        'approved'  => 'Aprovado',
        'completed' => 'Concluído',
        'rejected'  => 'Rejeitado',
        'expired'   => 'Expirado',
        'revised'   => 'Revisado',
        'cancelled' => 'Cancelado',
    ];

    public const COLORS = [
        'draft'     => '#9CA3AF',
        'sent'      => '#3B82F6',
        'approved'  => '#10B981',
        'completed' => '#059669',
        'rejected'  => '#EF4444',
        'expired'   => '#F59E0B',
        'revised'   => '#8B5CF6',
        'cancelled' => '#6B7280',
    ];

    public const ICONS = [
        'draft'     => 'mdi-file-document-edit',
        'sent'      => 'mdi-send',
        'approved'  => 'mdi-check-circle',
        'completed' => 'mdi-check-circle-outline',
        'rejected'  => 'mdi-close-circle',
        'expired'   => 'mdi-timer-off',
        'revised'   => 'mdi-file-compare',
        'cancelled' => 'mdi-cancel',
    ];

    public function getName(): string
    {
        return self::NAMES[ $this->value ];
    }

    public function getColor(): string
    {
        return self::COLORS[ $this->value ];
    }

    public function getIcon(): string
    {
        return self::ICONS[ $this->value ];
    }

    public function getSlug(): string
    {
        return $this->value;
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
