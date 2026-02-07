<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum para os status de compartilhamento de fatura.
 */
enum InvoiceShareStatus: string
{
    case ACTIVE = 'active';
    case REJECTED = 'rejected'; // Cliente contestou/rejeitou a fatura
    case EXPIRED = 'expired';

    /**
     * Retorna a descrição amigável do status.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativo',
            self::REJECTED => 'Rejeitado',
            self::EXPIRED => 'Expirado',
        };
    }

    /**
     * Retorna a cor associada ao status para exibição em badges.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => '#0d6efd', // Primary blue
            self::REJECTED => '#dc3545', // Danger red
            self::EXPIRED => '#6c757d', // Secondary gray
        };
    }
}
