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
    use \App\Traits\Enums\HasStatusEnumMethods;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DELETED = 'deleted';

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
        return array_merge([
            'value' => $this->value,
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
        ]);
    }
}
