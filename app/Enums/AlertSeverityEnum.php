<?php

declare(strict_types=1);

namespace App\Enums;

enum AlertSeverityEnum: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    use \App\Traits\Enums\HasStatusEnumMethods;

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

    public function getDescription(): string
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
            self::INFO => 'info',
            self::WARNING => 'warning',
            self::ERROR => 'danger',
            self::CRITICAL => 'danger',
        };
    }

    public function getColor(): string
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
            self::INFO => 'info-circle',
            self::WARNING => 'exclamation-triangle',
            self::ERROR => 'x-circle',
            self::CRITICAL => 'exclamation-octagon',
        };
    }

    public function getIcon(): string
    {
        return 'bi-'.$this->icon();
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

    public function isActive(): bool
    {
        return true;
    }

    public function isFinished(): bool
    {
        return false;
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
            self::WARNING => 5,
            self::ERROR => 1,
            self::CRITICAL => 0,
        };
    }

    public function getMetadata(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'description' => $this->getDescription(),
            'color' => $this->color(),
            'color_hex' => $this->getColor(),
            'icon' => $this->icon(),
            'icon_class' => $this->getIcon(),
            'priority' => $this->priority(),
            'should_notify' => $this->shouldNotify(),
            'notification_delay' => $this->notificationDelay(),
            'is_active' => $this->isActive(),
            'is_finished' => $this->isFinished(),
        ];
    }
}
