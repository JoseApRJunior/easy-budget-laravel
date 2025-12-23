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

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE => 'Cliente ativo no sistema',
            self::INACTIVE => 'Cliente inativo temporariamente',
            self::DELETED => 'Cliente removido do sistema',
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => '#28a745',   // Verde
            self::INACTIVE => '#ffc107', // Amarelo
            self::DELETED => '#dc3545',  // Vermelho
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::ACTIVE => 'check-circle',
            self::INACTIVE => 'pause-circle',
            self::DELETED => 'x-circle',
        };
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * {@inheritdoc}
     */
    public function isFinished(): bool
    {
        return $this === self::DELETED;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(): array
    {
        return [
            'description' => $this->getDescription(),
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
            'is_active' => $this->isActive(),
            'is_finished' => $this->isFinished(),
            'can_be_edited' => $this->canBeEdited(),
            'can_receive_services' => $this->canReceiveServices(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function fromString(string $value): ?self
    {
        foreach (self::cases() as $status) {
            if ($status->value === $value) {
                return $status;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getOptions(bool $includeFinished = true): array
    {
        $options = [];
        foreach (self::cases() as $status) {
            if (! $includeFinished && $status->isFinished()) {
                continue;
            }
            $options[$status->value] = $status->getDescription();
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public static function getOrdered(bool $includeFinished = true): array
    {
        $ordered = [
            self::ACTIVE,    // Prioridade 1
            self::INACTIVE,  // Prioridade 2
        ];

        if ($includeFinished) {
            $ordered[] = self::DELETED; // Prioridade 3
        }

        return $ordered;
    }

    /**
     * {@inheritdoc}
     */
    public static function calculateMetrics(array $statuses): array
    {
        $metrics = [
            'total' => count($statuses),
            'active' => 0,
            'inactive' => 0,
            'deleted' => 0,
            'active_percentage' => 0,
            'deleted_percentage' => 0,
        ];

        foreach ($statuses as $status) {
            if ($status->isActive()) {
                $metrics['active']++;
            } elseif ($status->isFinished()) {
                $metrics['deleted']++;
            } else {
                $metrics['inactive']++;
            }
        }

        if ($metrics['total'] > 0) {
            $metrics['active_percentage'] = round(($metrics['active'] / $metrics['total']) * 100, 2);
            $metrics['deleted_percentage'] = round(($metrics['deleted'] / $metrics['total']) * 100, 2);
        }

        return $metrics;
    }

    /**
     * Verifica se o cliente pode ser editado
     */
    public function canBeEdited(): bool
    {
        return $this !== self::DELETED;
    }

    /**
     * Verifica se o cliente pode receber orçamentos/serviços
     */
    public function canReceiveServices(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Retorna a cor de fundo para badges
     */
    public function getBadgeColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-success-subtle text-success',
            self::INACTIVE => 'bg-warning-subtle text-warning',
            self::DELETED => 'bg-danger-subtle text-danger',
        };
    }

    /**
     * Retorna todos os status disponíveis (método legado para compatibilidade)
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return self::getOptions();
    }

    /**
     * Retorna os status ativos para dropdowns (método legado para compatibilidade)
     *
     * @return array<string, string>
     */
    public static function activeOptions(): array
    {
        return self::getOptions(includeFinished: false);
    }
}
