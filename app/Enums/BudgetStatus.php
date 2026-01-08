<?php

declare(strict_types=1);

namespace App\Enums;

enum BudgetStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    use \App\Traits\Enums\HasStatusEnumMethods;

    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case IN_PROGRESS = 'in_progress';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

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
            self::IN_PROGRESS => 'Em Andamento',
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
            self::DRAFT => 'Orçamento em elaboração, permite modificações',
            self::PENDING => 'Aguardando aprovação do cliente',
            self::APPROVED => 'Aprovado pelo cliente',
            self::REJECTED => 'Rejeitado pelo cliente',
            self::IN_PROGRESS => 'Serviços vinculados em execução',
            self::CANCELLED => 'Orçamento cancelado',
            self::COMPLETED => 'Orçamento finalizado com sucesso',
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
            self::IN_PROGRESS => 'primary',
            self::CANCELLED => 'gray',
            self::COMPLETED => 'success',
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
            self::APPROVED => '#10B981',
            self::REJECTED => '#DC2626',
            self::IN_PROGRESS => '#007bff',
            self::CANCELLED => '#6c757d',
            self::COMPLETED => '#10B981',
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
            self::IN_PROGRESS => 'play-circle-fill',
            self::CANCELLED => 'x-circle-fill',
            self::COMPLETED => 'check-circle-fill',
        };
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
     * Retorna metadados do status
     */
    public function getMetadata(): array
    {
        return array_merge($this->defaultMetadata(), [
            'can_edit' => $this->canEdit(),
            'can_delete' => $this->canDelete(),
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
}
