<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    /** Fatura pendente de pagamento */
    case PENDING = 'pending';

    /** Fatura paga */
    case PAID = 'paid';

    /** Fatura cancelada */
    case CANCELLED = 'cancelled';

    /** Fatura vencida */
    case OVERDUE = 'overdue';

    /**
     * Retorna todos os valores do enum
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Retorna opções para select [value => label]
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    /**
     * Retorna todos os labels
     */
    public static function labels(): array
    {
        return array_map(fn($case) => $case->label(), self::cases());
    }

    /**
     * Verifica se um valor é válido
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Retorna o label do status
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::PAID => 'Pago',
            self::CANCELLED => 'Cancelado',
            self::OVERDUE => 'Vencido',
        };
    }

    /**
     * Retorna a descrição detalhada
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'Fatura pendente de pagamento',
            self::PAID => 'Fatura paga',
            self::CANCELLED => 'Fatura cancelada',
            self::OVERDUE => 'Fatura vencida',
        };
    }

    /**
     * Retorna a cor associada (Tailwind classes ou Hex)
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::CANCELLED => 'danger',
            self::OVERDUE => 'secondary',
        };
    }

    /**
     * Retorna a cor Hexadecimal (legado)
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => '#ffc107',
            self::PAID => '#198754',
            self::CANCELLED => '#dc3545',
            self::OVERDUE => '#6f42c1',
        };
    }

    /**
     * Retorna o ícone associado
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'hourglass-split',
            self::PAID => 'check-circle-fill',
            self::CANCELLED => 'x-circle-fill',
            self::OVERDUE => 'calendar-x-fill',
        };
    }

    /**
     * Retorna o ícone (Bootstrap Icons prefixado)
     */
    public function getIcon(): string
    {
        return 'bi-' . $this->icon();
    }

    /**
     * Verifica se o status indica que a fatura está pendente
     */
    public function isPending(): bool
    {
        return match ($this) {
            self::PENDING, self::OVERDUE => true,
            self::PAID, self::CANCELLED => false,
        };
    }

    /**
     * Verifica se o status indica atividade
     */
    public function isActive(): bool
    {
        return $this->isPending();
    }

    /**
     * Verifica se o status indica que a fatura foi finalizada
     */
    public function isFinished(): bool
    {
        return match ($this) {
            self::PAID, self::CANCELLED => true,
            self::PENDING, self::OVERDUE => false,
        };
    }

    /**
     * Verifica se o status indica que a fatura pode ser cobrada
     */
    public function isChargeable(): bool
    {
        return match ($this) {
            self::PENDING => true,
            default => false,
        };
    }

    /**
     * Verifica se é possível transitar para um determinado status
     */
    public function canTransitionTo(self $targetStatus): bool
    {
        $validTransitions = [
            self::PENDING->value => [self::PAID->value, self::CANCELLED->value, self::OVERDUE->value],
            self::PAID->value => [],
            self::CANCELLED->value => [],
            self::OVERDUE->value => [self::PAID->value, self::CANCELLED->value],
        ];

        return in_array($targetStatus->value, $validTransitions[$this->value] ?? []);
    }

    /**
     * Retorna a ordem de prioridade para exibição
     */
    public function getPriorityOrder(): int
    {
        return match ($this) {
            self::OVERDUE => 1,
            self::PENDING => 2,
            self::PAID => 3,
            self::CANCELLED => 4,
        };
    }

    /**
     * Retorna metadados do status
     */
    public function getMetadata(): array
    {
        return [
            'label' => $this->label(),
            'description' => $this->getDescription(),
            'color' => $this->color(),
            'color_hex' => $this->getColor(),
            'icon' => $this->icon(),
            'icon_class' => $this->getIcon(),
            'is_pending' => $this->isPending(),
            'is_active' => $this->isActive(),
            'is_finished' => $this->isFinished(),
            'is_chargeable' => $this->isChargeable(),
            'priority_order' => $this->getPriorityOrder(),
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
            return $a->getPriorityOrder() <=> $b->getPriorityOrder();
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
