<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    use \App\Traits\Enums\HasStatusEnumMethods;

    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

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

    /**
     * Retorna a cor Hexadecimal (Sincronizado com theme.php)
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => config('theme.colors.warning'),
            self::PROCESSING => config('theme.colors.info'),
            self::COMPLETED => config('theme.colors.success'),
            self::FAILED => config('theme.colors.error'),
            self::REFUNDED => config('theme.colors.secondary'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'hourglass-split',
            self::PROCESSING => 'arrow-repeat',
            self::COMPLETED => 'check-circle-fill',
            self::FAILED => 'x-circle-fill',
            self::REFUNDED => 'arrow-counterclockwise',
        };
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

    /**
     * Retorna metadados completos do status
     */
    public function getMetadata(): array
    {
        return array_merge($this->defaultMetadata(), [
            'is_successful' => $this->isSuccessful(),
        ]);
    }
}
