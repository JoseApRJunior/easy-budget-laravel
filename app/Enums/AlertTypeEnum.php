<?php

declare(strict_types=1);

namespace App\Enums;

enum AlertTypeEnum: string
{
    case PERFORMANCE = 'performance';
    case SECURITY = 'security';
    case AVAILABILITY = 'availability';
    case RESOURCE = 'resource';
    case BUSINESS = 'business';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::PERFORMANCE => 'Performance',
            self::SECURITY => 'Segurança',
            self::AVAILABILITY => 'Disponibilidade',
            self::RESOURCE => 'Recursos',
            self::BUSINESS => 'Negócios',
            self::SYSTEM => 'Sistema',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PERFORMANCE => 'Alertas relacionados à performance do sistema',
            self::SECURITY => 'Alertas de segurança e tentativas de acesso não autorizado',
            self::AVAILABILITY => 'Alertas de disponibilidade e uptime do sistema',
            self::RESOURCE => 'Alertas de uso de recursos (CPU, memória, disco)',
            self::BUSINESS => 'Alertas relacionados à lógica de negócios',
            self::SYSTEM => 'Alertas gerais do sistema',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PERFORMANCE => '#f59e0b',
            self::SECURITY => '#ef4444',
            self::AVAILABILITY => '#3b82f6',
            self::RESOURCE => '#10b981',
            self::BUSINESS => '#8b5cf6',
            self::SYSTEM => '#6b7280',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PERFORMANCE => 'bi-speedometer2',
            self::SECURITY => 'bi-shield-exclamation',
            self::AVAILABILITY => 'bi-wifi',
            self::RESOURCE => 'bi-server',
            self::BUSINESS => 'bi-graph-up',
            self::SYSTEM => 'bi-gear',
        };
    }
}
