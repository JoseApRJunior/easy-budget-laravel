<?php

declare(strict_types=1);

namespace App\Enums;

enum BudgetStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

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
            self::DRAFT => 'Rascunho',
            self::PENDING => 'Pendente',
            self::APPROVED => 'Aprovado',
            self::REJECTED => 'Rejeitado',
            self::CANCELLED => 'Cancelado',
            self::COMPLETED => 'Concluído',
        };
    }

    /**
     * Retorna a descrição detalhada
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::DRAFT => 'Orçamento em elaboração',
            self::PENDING => 'Aguardando aprovação do cliente',
            self::APPROVED => 'Orçamento aprovado',
            self::REJECTED => 'Orçamento rejeitado',
            self::CANCELLED => 'Orçamento cancelado',
            self::COMPLETED => 'Orçamento concluído',
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
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::CANCELLED => 'gray',
            self::COMPLETED => 'primary',
        };
    }

    /**
     * Retorna a cor Hexadecimal (legado)
     */
    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => '#6c757d',
            self::PENDING => '#ffc107',
            self::APPROVED => '#28a745',
            self::REJECTED => '#dc3545',
            self::CANCELLED => '#6c757d',
            self::COMPLETED => '#007bff',
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
            self::APPROVED => 'check-circle-fill',
            self::REJECTED => 'x-circle-fill',
            self::CANCELLED => 'x-circle',
            self::COMPLETED => 'check-circle',
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
     * Verifica se o status indica que o orçamento está ativo
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::DRAFT, self::PENDING => true,
            default => false,
        };
    }

    /**
     * Verifica se o status indica que o orçamento foi finalizado
     */
    public function isFinished(): bool
    {
        return match ($this) {
            self::APPROVED, self::REJECTED, self::CANCELLED, self::COMPLETED => true,
            default => false,
        };
    }

    public function canEdit(): bool
    {
        return match ($this) {
            self::DRAFT, self::PENDING => true,
            default => false,
        };
    }

    public function canDelete(): bool
    {
        return match ($this) {
            self::DRAFT, self::CANCELLED => true,
            default => false,
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::PENDING, self::CANCELLED]),
            self::PENDING => in_array($newStatus, [self::APPROVED, self::REJECTED, self::CANCELLED]),
            self::APPROVED => in_array($newStatus, [self::COMPLETED, self::CANCELLED]),
            self::REJECTED => in_array($newStatus, [self::DRAFT, self::CANCELLED]),
            self::CANCELLED => in_array($newStatus, [self::DRAFT]),
            self::COMPLETED => false,
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
            'is_active' => $this->isActive(),
            'is_finished' => $this->isFinished(),
            'can_edit' => $this->canEdit(),
            'can_delete' => $this->canDelete(),
            'order_index' => $this->getOrderIndex(),
        ];
    }

    /**
     * Retorna transições permitidas para um status (array de strings)
     */
    public static function getAllowedTransitions(string $statusValue): array
    {
        $status = self::tryFrom($statusValue);
        if (! $status) {
            return [];
        }

        $transitions = [];
        foreach (self::cases() as $targetStatus) {
            if ($status->canTransitionTo($targetStatus)) {
                $transitions[] = $targetStatus->value;
            }
        }

        return $transitions;
    }

    /**
     * Retorna o índice de ordem para classificação
     */
    public function getOrderIndex(): int
    {
        return match ($this) {
            self::DRAFT => 1,
            self::PENDING => 2,
            self::APPROVED => 3,
            self::REJECTED => 4,
            self::CANCELLED => 5,
            self::COMPLETED => 6,
        };
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
