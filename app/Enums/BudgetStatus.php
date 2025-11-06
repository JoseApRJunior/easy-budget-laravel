<?php

declare(strict_types=1);

namespace App\Enums;

enum BudgetStatus: string
{
    case DRAFT     = 'draft';
    case PENDING   = 'pending';
    case APPROVED  = 'approved';
    case REJECTED  = 'rejected';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function canEdit(): bool
    {
        return match ( $this ) {
            self::DRAFT, self::PENDING => true,
            default                    => false
        };
    }

    public function canDelete(): bool
    {
        return match ( $this ) {
            self::DRAFT, self::CANCELLED => true,
            default                      => false
        };
    }

    public function canTransitionTo( self $newStatus ): bool
    {
        return match ( $this ) {
            self::DRAFT    => in_array( $newStatus, [ self::PENDING, self::CANCELLED ] ),
            self::PENDING  => in_array( $newStatus, [ self::APPROVED, self::REJECTED, self::CANCELLED ] ),
            self::APPROVED => in_array( $newStatus, [ self::COMPLETED, self::CANCELLED ] ),
            self::REJECTED => in_array( $newStatus, [ self::CANCELLED ] ),
            default        => false
        };
    }

    public static function values(): array
    {
        return array_column( self::cases(), 'value' );
    }

    public function label(): string
    {
        return match ( $this ) {
            self::DRAFT     => 'Rascunho',
            self::PENDING   => 'Pendente',
            self::APPROVED  => 'Aprovado',
            self::REJECTED  => 'Rejeitado',
            self::CANCELLED => 'Cancelado',
            self::COMPLETED => 'Concluído'
        };
    }

}
