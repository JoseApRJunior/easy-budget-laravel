<?php

declare(strict_types=1);

namespace App\Traits\Enums;

/**
 * Trait para implementar métodos comuns de StatusEnumInterface
 */
trait HasStatusEnumMethods
{
    /**
     * Retorna todos os valores do enum
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Retorna todos os labels do enum
     */
    public static function labels(): array
    {
        return array_map(fn($case) => method_exists($case, 'label') ? $case->label() : $case->name, self::cases());
    }

    /**
     * Verifica se um valor é válido para o enum
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
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
            if (!$includeFinished && method_exists($case, 'isFinished') && $case->isFinished()) {
                continue;
            }
            $options[$case->value] = method_exists($case, 'label') ? $case->label() : $case->name;
        }

        return $options;
    }

    /**
     * Retorna os status ordenados por prioridade/fluxo
     */
    public static function getOrdered(bool $includeFinished = true): array
    {
        // Implementação padrão: retorna todos os cases
        // Enums específicos podem sobrescrever este método se precisarem de ordem customizada
        if ($includeFinished) {
            return self::cases();
        }

        return array_filter(self::cases(), fn($case) => method_exists($case, 'isActive') ? $case->isActive() : true);
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
                'counts' => [],
            ];
        }

        $counts = [];
        $activeCount = 0;
        $finishedCount = 0;

        foreach ($statuses as $status) {
            $statusEnum = $status instanceof self ? $status : self::fromString((string) $status);
            if (!$statusEnum) {
                continue;
            }

            $counts[$statusEnum->value] = ($counts[$statusEnum->value] ?? 0) + 1;

            if (method_exists($statusEnum, 'isActive') && $statusEnum->isActive()) {
                $activeCount++;
            }

            if (method_exists($statusEnum, 'isFinished') && $statusEnum->isFinished()) {
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

    /**
     * Retorna metadados completos do status
     */
    public function getMetadata(): array
    {
        return [
            'value' => $this->value,
            'label' => method_exists($this, 'label') ? $this->label() : $this->name,
            'description' => method_exists($this, 'getDescription') ? $this->getDescription() : '',
            'color' => method_exists($this, 'color') ? $this->color() : (method_exists($this, 'getColor') ? $this->getColor() : ''),
            'color_hex' => method_exists($this, 'getColor') ? $this->getColor() : '',
            'icon' => method_exists($this, 'icon') ? $this->icon() : (method_exists($this, 'getIcon') ? $this->getIcon() : ''),
            'icon_class' => method_exists($this, 'getIcon') ? $this->getIcon() : '',
            'is_active' => method_exists($this, 'isActive') ? $this->isActive() : true,
            'is_finished' => method_exists($this, 'isFinished') ? $this->isFinished() : false,
        ];
    }
}
