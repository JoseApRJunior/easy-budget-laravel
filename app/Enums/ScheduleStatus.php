<?php

namespace App\Enums;

use App\Contracts\Interfaces\StatusEnumInterface;

enum ScheduleStatus: string implements StatusEnumInterface
{
    use \App\Traits\Enums\HasStatusEnumMethods;

    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

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
            self::CONFIRMED => 'Confirmado',
            self::COMPLETED => 'Concluído',
            self::CANCELLED => 'Cancelado',
            self::NO_SHOW => 'Não Compareceu',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'Agendamento pendente de confirmação',
            self::CONFIRMED => 'Agendamento confirmado',
            self::COMPLETED => 'Agendamento concluído',
            self::CANCELLED => 'Agendamento cancelado',
            self::NO_SHOW => 'Cliente não compareceu',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'secondary',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => '#ffc107',
            self::CONFIRMED => '#007bff',
            self::COMPLETED => '#28a745',
            self::CANCELLED => '#dc3545',
            self::NO_SHOW => '#6c757d',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'hourglass-split',
            self::CONFIRMED => 'check-circle',
            self::COMPLETED => 'check2-circle',
            self::CANCELLED => 'x-circle',
            self::NO_SHOW => 'slash-circle',
        };
    }

    public function getIcon(): string
    {
        return 'bi-' . $this->icon();
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::PENDING, self::CONFIRMED => true,
            self::COMPLETED, self::CANCELLED, self::NO_SHOW => false,
        };
    }

    public function isFinished(): bool
    {
        return match ($this) {
            self::COMPLETED, self::CANCELLED, self::NO_SHOW => true,
            self::PENDING, self::CONFIRMED => false,
        };
    }

    public function canEdit(): bool
    {
        return ! $this->isFinished();
    }

    public function canCancel(): bool
    {
        return $this->isActive();
    }

    public static function getOrdered(bool $includeFinished = true): array
    {
        $ordered = [
            self::PENDING,
            self::CONFIRMED,
        ];

        if ($includeFinished) {
            $ordered[] = self::COMPLETED;
            $ordered[] = self::CANCELLED;
            $ordered[] = self::NO_SHOW;
        }

        return $ordered;
    }

    public function getMetadata(): array
    {
        return array_merge($this->defaultMetadata(), [
            'can_edit' => $this->canEdit(),
            'can_cancel' => $this->canCancel(),
        ]);
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
