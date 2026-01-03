<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Interfaces\StatusEnumInterface;

/**
 * Enum para os status dos clientes
 *
 * Implementa StatusEnumInterface para garantir consistência
 * com o padrão de enums de status da aplicação.
 */
enum CustomerStatus: string implements StatusEnumInterface
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DELETED = 'deleted';

    public static function values(): array
    {        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    public static function labels(): array
    {        return array_map(fn (self $case) => $case->label(), self::cases());
    }

    public static function isValid(string $value): bool
    {        return in_array($value, self::values(), true);
    }

    public function label(): string
    {        return match ($this) {
            self::ACTIVE => 'Ativo',
            self::INACTIVE => 'Inativo',
            self::DELETED => 'Excluído',
        };
    }

    public function getDescription(): string
    {        return match ($this) {
            self::ACTIVE => 'Cliente ativo no sistema',
            self::INACTIVE => 'Cliente inativo temporariamente',
            self::DELETED => 'Cliente removido do sistema',
        };
    }

    public function color(): string
    {        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'warning',
            self::DELETED => 'danger',
        };
    }

    public function getColor(): string
    {        return match ($this) {
            self::ACTIVE => '#28a745',
            self::INACTIVE => '#ffc107',
            self::DELETED => '#dc3545',
        };
    }

    public function icon(): string
    {        return match ($this) {
            self::ACTIVE => 'check-circle',
            self::INACTIVE => 'pause-circle',
            self::DELETED => 'x-circle',
        };
    }

    public function getIcon(): string
    {        return 'bi-' . $this->icon();
    }

    public function isActive(): bool
    {        return $this === self::ACTIVE;
    }

    public function isFinished(): bool
    {        return $this === self::DELETED;
    }

    public function canBeEdited(): bool
    {        return $this !== self::DELETED;
    }

    public function canReceiveServices(): bool
    {        return $this === self::ACTIVE;
    }

    public static function getOrdered(bool $includeFinished = true): array
    {        $ordered = [
            self::ACTIVE,
            self::INACTIVE,
        ];

        if ($includeFinished) {
            $ordered[] = self::DELETED;
        }

        return $ordered;
    }

    public function getMetadata(): array
    {
        return [
            'label' => $this->label(),
            'description' => $this->getDescription(),
            'color' => $this->color(),
            'color_hex' => $this->getColor(),
            'icon' => $this->icon(),
            'icon_class' => $this->getIcon(),
            'is_active' => $this->isActive(),
            'is_finished' => $this->isFinished(),
            'can_be_edited' => $this->canBeEdited(),
            'can_receive_services' => $this->canReceiveServices(),
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
