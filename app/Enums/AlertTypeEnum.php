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

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    public static function labels(): array
    {
        return array_map(fn (self $case) => $case->label(), self::cases());
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

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
            self::PERFORMANCE => 'warning',
            self::SECURITY => 'danger',
            self::AVAILABILITY => 'info',
            self::RESOURCE => 'success',
            self::BUSINESS => 'primary',
            self::SYSTEM => 'secondary',
        };
    }

    public function getColor(): string
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
            self::PERFORMANCE => 'speedometer2',
            self::SECURITY => 'shield-exclamation',
            self::AVAILABILITY => 'wifi',
            self::RESOURCE => 'server',
            self::BUSINESS => 'graph-up',
            self::SYSTEM => 'gear',
        };
    }

    public function getIcon(): string
    {
        return 'bi-' . $this->icon();
    }

    public function getMetadata(): array
    {
        return [
            'label' => $this->label(),
            'description' => $this->description(),
            'color' => $this->color(),
            'color_hex' => $this->getColor(),
            'icon' => $this->icon(),
            'icon_class' => $this->getIcon(),
        ];
    }
}
