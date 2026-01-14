<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    use \App\Traits\Enums\HasStatusEnumMethods;

    /** Serviço em elaboração, permite modificações */
    case DRAFT = 'draft';

    /** Serviço pendente */
    case PENDING = 'pending';

    /** Serviço em processo de agendamento */
    case SCHEDULING = 'scheduling';

    /** Serviço em preparação */
    case PREPARING = 'preparing';

    /** Serviço em andamento */
    case IN_PROGRESS = 'in_progress';

    /** Serviço em espera/pausado */
    case ON_HOLD = 'on_hold';

    /** Serviço agendado */
    case SCHEDULED = 'scheduled';

    /** Serviço concluído */
    case COMPLETED = 'completed';

    /** Serviço concluído parcialmente */
    case PARTIAL = 'partial';

    /** Serviço cancelado */
    case CANCELLED = 'cancelled';

    /** Serviço não realizado */
    case NOT_PERFORMED = 'not_performed';

    /** Serviço expirado */
    case EXPIRED = 'expired';

    /** Status de aprovação para clientes */
    case APPROVED = 'approved';

    /** Status de rejeição para clientes */
    case REJECTED = 'rejected';

    /**
     * Retorna o label do status
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Rascunho',
            self::PENDING => 'Pendente',
            self::SCHEDULING => 'Agendando',
            self::PREPARING => 'Preparando',
            self::IN_PROGRESS => 'Em Andamento',
            self::ON_HOLD => 'Em Espera',
            self::SCHEDULED => 'Agendado',
            self::COMPLETED => 'Concluído',
            self::PARTIAL => 'Parcial',
            self::CANCELLED => 'Cancelado',
            self::NOT_PERFORMED => 'Não Realizado',
            self::EXPIRED => 'Expirado',
            self::APPROVED => 'Aprovado',
            self::REJECTED => 'Rejeitado',
        };
    }

    /**
     * Retorna a descrição detalhada
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::DRAFT => 'Serviço em elaboração, permite modificações',
            self::PENDING => 'Serviço pendente',
            self::SCHEDULING => 'Serviço em processo de agendamento',
            self::PREPARING => 'Serviço em preparação',
            self::IN_PROGRESS => 'Serviço em andamento',
            self::ON_HOLD => 'Serviço em espera/pausado',
            self::SCHEDULED => 'Serviço agendado',
            self::COMPLETED => 'Serviço concluído',
            self::PARTIAL => 'Serviço concluído parcialmente',
            self::CANCELLED => 'Serviço cancelado',
            self::NOT_PERFORMED => 'Serviço não realizado',
            self::EXPIRED => 'Serviço expirado',
            self::APPROVED => 'Aprovado pelo cliente',
            self::REJECTED => 'Rejeitado pelo cliente',
        };
    }

    /**
     * Retorna a cor associada (Tailwind classes ou Hex)
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'warning',
            self::SCHEDULING => 'info',
            self::PREPARING => 'warning',
            self::IN_PROGRESS => 'primary',
            self::ON_HOLD => 'secondary',
            self::SCHEDULED => 'info',
            self::COMPLETED => 'success',
            self::PARTIAL => 'success',
            self::CANCELLED => 'danger',
            self::NOT_PERFORMED => 'danger',
            self::EXPIRED => 'danger',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    /**
     * Retorna a cor Hexadecimal (Sincronizado com theme.php)
     */
    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => config('theme.colors.secondary'),
            self::PENDING => config('theme.colors.warning'),
            self::SCHEDULING => config('theme.colors.info'),
            self::PREPARING => config('theme.colors.warning'),
            self::IN_PROGRESS => config('theme.colors.primary'),
            self::ON_HOLD => config('theme.colors.secondary'),
            self::SCHEDULED => config('theme.colors.info'),
            self::COMPLETED => config('theme.colors.success'),
            self::PARTIAL => config('theme.colors.success'),
            self::CANCELLED => config('theme.colors.error'),
            self::NOT_PERFORMED => config('theme.colors.error'),
            self::EXPIRED => config('theme.colors.error'),
            self::APPROVED => config('theme.colors.success'),
            self::REJECTED => config('theme.colors.error'),
        };
    }

    /**
     * Retorna o ícone associado
     */
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'pencil-square',
            self::PENDING => 'hourglass-split',
            self::SCHEDULING => 'calendar-check',
            self::PREPARING => 'tools',
            self::IN_PROGRESS => 'play-circle-fill',
            self::ON_HOLD => 'pause-circle',
            self::SCHEDULED => 'calendar-plus',
            self::COMPLETED => 'check-circle-fill',
            self::PARTIAL => 'check-circle-fill',
            self::CANCELLED => 'x-circle-fill',
            self::NOT_PERFORMED => 'slash-circle',
            self::EXPIRED => 'calendar-x',
            self::APPROVED => 'check-circle-fill',
            self::REJECTED => 'x-circle-fill',
        };
    }

    /**
     * Verifica se o status indica que o serviço está ativo
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::DRAFT, self::PENDING, self::SCHEDULING, self::PREPARING,
            self::IN_PROGRESS, self::ON_HOLD, self::SCHEDULED => true,
            self::COMPLETED, self::PARTIAL, self::CANCELLED,
            self::NOT_PERFORMED, self::EXPIRED, self::APPROVED, self::REJECTED => false,
        };
    }

    /**
     * Verifica se o status indica que o serviço foi finalizado
     */
    public function isFinished(): bool
    {
        return match ($this) {
            self::COMPLETED, self::PARTIAL, self::CANCELLED,
            self::NOT_PERFORMED, self::EXPIRED, self::APPROVED, self::REJECTED => true,
            self::DRAFT, self::PENDING, self::SCHEDULING, self::PREPARING,
            self::IN_PROGRESS, self::ON_HOLD, self::SCHEDULED => false,
        };
    }

    /**
     * Verifica se o status indica que o serviço pode ser executado
     */
    public function isExecutable(): bool
    {
        return match ($this) {
            self::SCHEDULED, self::IN_PROGRESS => true,
            self::DRAFT, self::PENDING, self::SCHEDULING, self::PREPARING,
            self::ON_HOLD, self::COMPLETED, self::PARTIAL, self::CANCELLED,
            self::NOT_PERFORMED, self::EXPIRED, self::APPROVED, self::REJECTED => false,
        };
    }

    /**
     * Verifica se pode ser editado
     */
    public function canEdit(): bool
    {
        return match ($this) {
            self::DRAFT, self::SCHEDULED, self::PREPARING, self::ON_HOLD,
            self::IN_PROGRESS, self::PARTIAL => true,
            default => false,
        };
    }

    /**
     * Retorna o índice de ordem para classificação
     */
    public function getOrderIndex(): int
    {
        return match ($this) {
            self::DRAFT => 1,
            self::PENDING => 2,
            self::SCHEDULING => 3,
            self::SCHEDULED => 4,
            self::PREPARING => 5,
            self::IN_PROGRESS => 6,
            self::ON_HOLD => 7,
            self::PARTIAL => 8,
            self::COMPLETED => 9,
            self::APPROVED => 10,
            self::REJECTED => 11,
            self::CANCELLED => 12,
            self::NOT_PERFORMED => 13,
            self::EXPIRED => 14,
        };
    }

    /**
     * Retorna metadados do status
     */
    public function getMetadata(): array
    {
        return array_merge($this->defaultMetadata(), [
            'is_executable' => $this->isExecutable(),
            'can_edit' => $this->canEdit(),
            'order_index' => $this->getOrderIndex(),
        ]);
    }

    private function defaultMetadata(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'description' => $this->getDescription(),
            'color' => $this->color(),
            'color_hex' => $this->getColor(),
            'icon' => $this->icon(),
            'icon_class' => $this->getIcon(),
            'is_active' => $this->isActive(),
            'is_finished' => $this->isFinished(),
        ];
    }

    /**
     * Retorna as transições permitidas para um status
     */
    public static function getAllowedTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            self::DRAFT->value => [self::PENDING->value, self::CANCELLED->value],
            self::PENDING->value => [self::SCHEDULING->value, self::SCHEDULED->value, self::PREPARING->value, self::ON_HOLD->value, self::CANCELLED->value, self::EXPIRED->value, self::DRAFT->value],
            self::SCHEDULING->value => [self::SCHEDULED->value, self::NOT_PERFORMED->value, self::CANCELLED->value, self::PENDING->value],
            self::SCHEDULED->value => [self::PREPARING->value, self::NOT_PERFORMED->value, self::CANCELLED->value, self::ON_HOLD->value],
            self::PREPARING->value => [self::IN_PROGRESS->value, self::NOT_PERFORMED->value, self::CANCELLED->value, self::ON_HOLD->value],
            self::IN_PROGRESS->value => [self::COMPLETED->value, self::PARTIAL->value, self::ON_HOLD->value, self::CANCELLED->value],
            self::ON_HOLD->value => [self::SCHEDULED->value, self::PREPARING->value, self::IN_PROGRESS->value, self::NOT_PERFORMED->value, self::CANCELLED->value],
            self::CANCELLED->value => [self::DRAFT->value],
            self::EXPIRED->value => [self::DRAFT->value],
            default => [],
        };
    }

    /**
     * Retorna todos os status finais
     */
    public static function getFinalStatuses(): array
    {
        return [
            self::COMPLETED->value,
            self::PARTIAL->value,
            self::CANCELLED->value,
            self::NOT_PERFORMED->value,
            self::EXPIRED->value,
            self::APPROVED->value,
            self::REJECTED->value,
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
     * Ordena status por prioridade para exibição
     */
    public static function getOrdered(bool $includeFinished = true): array
    {
        $cases = self::cases();

        if (! $includeFinished) {
            $cases = array_filter($cases, fn ($case) => ! $case->isFinished());
        }

        usort($cases, function ($a, $b) {
            return $a->getOrderIndex() <=> $b->getOrderIndex();
        });

        return array_values($cases);
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
