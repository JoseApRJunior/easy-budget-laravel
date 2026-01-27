<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum que representa os possíveis status de um chamado de suporte
 *
 * Este enum define todos os status disponíveis para os chamados
 * conforme especificado na estrutura da tabela supports.
 *
 * Implementa StatusEnumInterface para garantir consistência
 * com outros enums de status do sistema.
 *
 * Funcionalidades disponíveis:
 * - Descrições detalhadas de cada status
 * - Cores e ícones para interface
 * - Controle de fluxo e transições válidas
 * - Verificação de status ativo/finalizado
 * - Metadados completos para cada status
 *
 * @implements \App\Contracts\Interfaces\StatusEnumInterface
 *
 * @example Uso básico:
 * ```php
 * $status = SupportStatus::ABERTO;
 * echo $status->getDescription(); // "Chamado aberto, aguardando atendimento"
 * echo $status->getColor(); // "#FFA500"
 * ```
 * @example Controle de fluxo:
 * ```php
 * $currentStatus = SupportStatus::EM_ANDAMENTO;
 * $nextStatus = $currentStatus->getNextStatus(); // SupportStatus::AGUARDANDO_RESPOSTA
 *
 * if ($currentStatus->canTransitionTo(SupportStatus::RESOLVIDO)) {
 *     // Realizar transição
 * }
 * ```
 * @example Uso em collections/queries:
 * ```php
 * $activeSupports = SupportStatus::getActive();
 * $finishedSupports = SupportStatus::getFinished();
 *
 * $supports = Support::whereIn('status', $activeSupports)->get();
 * ```
 */
enum SupportStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    use \App\Traits\Enums\HasStatusEnumMethods;

    case OPEN = 'open';
    case RESPONDED = 'responded';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
    case IN_PROGRESS = 'in_progress';
    case AWAITING_RESPONSE = 'awaiting_response';
    case CANCELLED = 'cancelled';

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
            self::OPEN => 'Aberto',
            self::RESPONDED => 'Respondido',
            self::RESOLVED => 'Resolvido',
            self::CLOSED => 'Fechado',
            self::IN_PROGRESS => 'Em Andamento',
            self::AWAITING_RESPONSE => 'Aguardando Resposta',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::OPEN => 'Chamado aberto, aguardando atendimento',
            self::RESPONDED => 'Chamado respondido pela equipe',
            self::RESOLVED => 'Chamado resolvido',
            self::CLOSED => 'Chamado fechado',
            self::IN_PROGRESS => 'Chamado em andamento',
            self::AWAITING_RESPONSE => 'Aguardando resposta do cliente',
            self::CANCELLED => 'Chamado cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'warning',
            self::RESPONDED => 'primary',
            self::RESOLVED => 'success',
            self::CLOSED => 'secondary',
            self::IN_PROGRESS => 'info',
            self::AWAITING_RESPONSE => 'warning',
            self::CANCELLED => 'danger',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OPEN => '#FFA500',
            self::RESPONDED => '#007BFF',
            self::RESOLVED => '#28A745',
            self::CLOSED => '#6C757D',
            self::IN_PROGRESS => '#17A2B8',
            self::AWAITING_RESPONSE => '#FFC107',
            self::CANCELLED => '#DC3545',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::OPEN => 'envelope-paper',
            self::RESPONDED => 'reply',
            self::RESOLVED => 'check-circle',
            self::CLOSED => 'lock',
            self::IN_PROGRESS => 'gear',
            self::AWAITING_RESPONSE => 'clock',
            self::CANCELLED => 'x-circle',
        };
    }

    public function getIcon(): string
    {
        return 'bi-'.$this->icon();
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::OPEN, self::RESPONDED, self::IN_PROGRESS, self::AWAITING_RESPONSE => true,
            self::RESOLVED, self::CLOSED, self::CANCELLED => false,
        };
    }

    public function isFinished(): bool
    {
        return ! $this->isActive();
    }

    public function getValidTransitions(): array
    {
        return match ($this) {
            self::OPEN => [self::IN_PROGRESS, self::CANCELLED],
            self::IN_PROGRESS => [self::RESPONDED, self::AWAITING_RESPONSE, self::RESOLVED, self::CANCELLED],
            self::RESPONDED => [self::AWAITING_RESPONSE, self::RESOLVED, self::CLOSED],
            self::AWAITING_RESPONSE => [self::IN_PROGRESS, self::RESOLVED, self::CANCELLED],
            self::RESOLVED => [self::CLOSED, self::IN_PROGRESS],
            self::CLOSED, self::CANCELLED => [self::OPEN],
        };
    }

    public function canTransitionTo(SupportStatus $targetStatus): bool
    {
        return in_array($targetStatus, $this->getValidTransitions(), true);
    }

    public static function getOrdered(bool $includeFinished = true): array
    {
        $ordered = [
            self::OPEN,
            self::IN_PROGRESS,
            self::RESPONDED,
            self::AWAITING_RESPONSE,
        ];

        if ($includeFinished) {
            $ordered[] = self::RESOLVED;
            $ordered[] = self::CLOSED;
            $ordered[] = self::CANCELLED;
        }

        return $ordered;
    }

    public function getMetadata(): array
    {
        return array_merge($this->defaultMetadata(), [
            'valid_transitions' => $this->getValidTransitions(),
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
