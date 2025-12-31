<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

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
            self::PENDING => 'Pendente',
            self::PROCESSING => 'Em Processamento',
            self::COMPLETED => 'Concluído',
            self::FAILED => 'Falhou',
            self::REFUNDED => 'Estornado',
        };
    }

    public function getDescription(): string
    {
        return $this->label();
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::REFUNDED => 'secondary',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => '#ffc107',
            self::PROCESSING => '#17a2b8',
            self::COMPLETED => '#28a745',
            self::FAILED => '#dc3545',
            self::REFUNDED => '#6c757d',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::PROCESSING => 'arrow-repeat',
            self::COMPLETED => 'check-circle-fill',
            self::FAILED => 'x-circle-fill',
            self::REFUNDED => 'arrow-counterclockwise',
        };
    }

    public function getIcon(): string
    {
        return 'bi-' . $this->icon();
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::PENDING, self::PROCESSING => true,
            self::COMPLETED, self::FAILED, self::REFUNDED => false,
        };
    }

    public function isFinished(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FAILED, self::REFUNDED => true,
            self::PENDING, self::PROCESSING => false,
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    public function getValidTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::PROCESSING, self::FAILED],
            self::PROCESSING => [self::COMPLETED, self::FAILED],
            self::COMPLETED => [self::REFUNDED],
            self::FAILED => [self::PENDING],
            self::REFUNDED => [],
        };
    }

    public function canTransitionTo(PaymentStatus $targetStatus): bool
    {
        return in_array($targetStatus, $this->getValidTransitions(), true);
    }

    public static function getAll(): array
    {
        return self::cases();
    }

    public static function getActive(): array
    {
        return array_filter(self::cases(), fn (self $status) => $status->isActive());
    }

    public static function getFinished(): array
    {
        return array_filter(self::cases(), fn (self $status) => $status->isFinished());
    }

    public static function getOrdered(bool $includeFinished = true): array
    {
        $ordered = [
            self::PENDING,
            self::PROCESSING,
            self::COMPLETED,
            self::FAILED,
            self::REFUNDED,
        ];

        if (! $includeFinished) {
            return array_filter($ordered, fn ($status) => ! $status->isFinished());
        }

        return $ordered;
    }

    public function getMetadata(): array
    {
        return [
            'label' => $this->label(),
            'description' => $this->label(),
            'color' => $this->color(),
            'color_hex' => $this->getColor(),
            'icon' => $this->icon(),
            'icon_class' => $this->getIcon(),
            'is_active' => $this->isActive(),
            'is_finished' => $this->isFinished(),
            'is_successful' => $this->isSuccessful(),
        ];
    }

    /**
     * Cria instância do enum a partir de string
     */
    public static function fromString(string $value): ?self
    {
        try {
            return self::from($value);
        } catch (\ValueError $e) {
            return null;
        }
    }

    /**
     * Retorna opções formatadas para uso em formulários/selects
     */
    public static function getOptions(bool $includeFinished = true): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            if (! $includeFinished && $case->isFinished()) {
                continue;
            }
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * Calcula métricas de status para dashboards
     */
    public static function calculateMetrics(array $statuses): array
    {
        $total = count($statuses);
        if ($total === 0) {
            return [
                'total' => 0,
                'active' => 0,
                'finished' => 0,
                'percentages' => [],
            ];
        }

        $counts = [];
        $activeCount = 0;
        $finishedCount = 0;

        foreach ($statuses as $status) {
            $statusEnum = $status instanceof self ? $status : self::fromString((string) $status);
            if (! $statusEnum) {
                continue;
            }

            $counts[$statusEnum->value] = ($counts[$statusEnum->value] ?? 0) + 1;

            if ($statusEnum->isActive()) {
                $activeCount++;
            }

            if ($statusEnum->isFinished()) {
                $finishedCount++;
            }
        }

        $percentages = [];
        foreach ($counts as $value => $count) {
            $percentages[$value] = round(($count / $total) * 100, 1);
        }

        return [
            'total' => $total,
            'active' => $activeCount,
            'finished' => $finishedCount,
            'percentages' => $percentages,
            'counts' => $counts,
        ];
    }
}
