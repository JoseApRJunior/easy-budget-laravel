<?php

declare(strict_types=1);

namespace App\Enums;

enum AlertSeverityEnum: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::INFO => 'Informação',
            self::WARNING => 'Aviso',
            self::ERROR => 'Erro',
            self::CRITICAL => 'Crítico',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::INFO => 'Informação geral, não requer ação imediata',
            self::WARNING => 'Condição que pode requerer atenção',
            self::ERROR => 'Problema que requer atenção',
            self::CRITICAL => 'Problema crítico que requer ação imediata',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INFO => '#3b82f6',
            self::WARNING => '#f59e0b',
            self::ERROR => '#ef4444',
            self::CRITICAL => '#dc2626',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::INFO => 'bi-info-circle',
            self::WARNING => 'bi-exclamation-triangle',
            self::ERROR => 'bi-x-circle',
            self::CRITICAL => 'bi-exclamation-octagon',
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4,
        };
    }

    public function shouldNotify(): bool
    {
        return match ($this) {
            self::INFO => false,
            self::WARNING => true,
            self::ERROR => true,
            self::CRITICAL => true,
        };
    }

    public function notificationDelay(): int
    {
        return match ($this) {
            self::INFO => 0,
            self::WARNING => 5, // 5 minutos
            self::ERROR => 1, // 1 minuto
            self::CRITICAL => 0, // Imediato
        };
    }
}
