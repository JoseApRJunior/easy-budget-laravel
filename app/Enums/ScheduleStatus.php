<?php

namespace App\Enums;

use App\Contracts\Interfaces\StatusEnumInterface;

enum ScheduleStatus: string implements StatusEnumInterface
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    /**
     * Retorna uma descrição para o status.
     */
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

    /**
     * Retorna a cor associada ao status para interface.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'secondary',
        };
    }

    /**
     * Retorna o ícone associado ao status.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'bi-hourglass-split',
            self::CONFIRMED => 'bi-check-circle',
            self::COMPLETED => 'bi-check2-circle',
            self::CANCELLED => 'bi-x-circle',
            self::NO_SHOW => 'bi-slash-circle',
        };
    }

    /**
     * Verifica se o status indica atividade.
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::PENDING, self::CONFIRMED => true,
            self::COMPLETED, self::CANCELLED, self::NO_SHOW => false,
        };
    }

    /**
     * Verifica se o status indica finalização.
     */
    public function isFinished(): bool
    {
        return match ($this) {
            self::COMPLETED, self::CANCELLED, self::NO_SHOW => true,
            self::PENDING, self::CONFIRMED => false,
        };
    }

    /**
     * Retorna metadados completos do status.
     */
    public function getMetadata(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
            'is_active' => $this->isActive(),
            'is_finished' => $this->isFinished(),
            'can_edit' => $this->canEdit(),
            'can_cancel' => $this->canCancel(),
        ];
    }

    /**
     * Cria instância do enum a partir de string.
     */
    public static function fromString(string $value): ?self
    {
        return self::isValid($value) ? self::from($value) : null;
    }

    /**
     * Retorna opções formatadas para uso em formulários/selects.
     */
    public static function getOptions(bool $includeFinished = true): array
    {
        $options = [];
        foreach (self::cases() as $status) {
            if (!$includeFinished && $status->isFinished()) {
                continue;
            }
            $options[$status->value] = $status->getLabel();
        }
        return $options;
    }

    /**
     * Ordena status por prioridade para exibição.
     */
    public static function getOrdered(bool $includeFinished = true): array
    {
        $activeStatuses = self::getActiveStatuses();
        $finalStatuses = self::getFinalStatuses();

        $ordered = [];
        foreach ($activeStatuses as $status) {
            $ordered[$status] = self::from($status)->getLabel();
        }

        if ($includeFinished) {
            foreach ($finalStatuses as $status) {
                $ordered[$status] = self::from($status)->getLabel();
            }
        }

        return $ordered;
    }

    /**
     * Calcula métricas de status para dashboards.
     */
    public static function calculateMetrics(array $statuses): array
    {
        $metrics = [
            'total' => count($statuses),
            'pending' => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'no_show' => 0,
            'active' => 0,
            'finished' => 0,
        ];

        foreach ($statuses as $status) {
            if (!($status instanceof self)) {
                continue;
            }

            $metrics[$status->value]++;
            if ($status->isActive()) {
                $metrics['active']++;
            }
            if ($status->isFinished()) {
                $metrics['finished']++;
            }
        }

        return $metrics;
    }

    /**
     * Retorna o label legível para exibição.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::CONFIRMED => 'Confirmado',
            self::COMPLETED => 'Concluído',
            self::CANCELLED => 'Cancelado',
            self::NO_SHOW => 'No-show',
        };
    }

    /**
     * Retorna a classe CSS para o badge.
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'secondary',
        };
    }

    /**
     * Retorna todos os valores possíveis.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Verifica se um valor é válido.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values());
    }

    /**
     * Retorna os status que podem transitar para este status.
     */
    public static function getAllowedTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            self::PENDING->value => [self::CONFIRMED->value, self::CANCELLED->value],
            self::CONFIRMED->value => [self::COMPLETED->value, self::CANCELLED->value, self::NO_SHOW->value],
            default => [], // Status finais não têm transições
        };
    }

    /**
     * Retorna os status finais.
     */
    public static function getFinalStatuses(): array
    {
        return [
            self::COMPLETED->value,
            self::CANCELLED->value,
            self::NO_SHOW->value,
        ];
    }

    /**
     * Retorna os status ativos (que ainda podem ser modificados).
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::PENDING->value,
            self::CONFIRMED->value,
        ];
    }

    /**
     * Verifica se o status permite edição.
     */
    public function canEdit(): bool
    {
        return match ($this) {
            self::PENDING, self::CONFIRMED => true,
            self::COMPLETED, self::CANCELLED, self::NO_SHOW => false,
        };
    }

    /**
     * Verifica se o status permite cancelamento.
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::PENDING, self::CONFIRMED => true,
            self::COMPLETED, self::CANCELLED, self::NO_SHOW => false,
        };
    }

    /**
     * Verifica se o status é final (não pode ser modificado).
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::CANCELLED, self::NO_SHOW => true,
            self::PENDING, self::CONFIRMED => false,
        };
    }
}
