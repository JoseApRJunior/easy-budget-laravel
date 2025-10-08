<?php

namespace app\enums;

final class ServiceStatusEnum
{
    public const PENDING = 'PENDING';
    public const SCHEDULING = 'SCHEDULING';
    public const PREPARING = 'PREPARING';
    public const IN_PROGRESS = 'IN_PROGRESS';
    public const ON_HOLD = 'ON_HOLD';
    public const SCHEDULED = 'SCHEDULED';
    public const COMPLETED = 'COMPLETED';
    public const PARTIAL = 'PARTIAL';
    public const CANCELLED = 'CANCELLED';
    public const NOT_PERFORMED = 'NOT_PERFORMED';

    private const ALLOWED_TRANSITIONS = [
        self::PENDING => [ self::SCHEDULING, self::CANCELLED ],
        self::SCHEDULING => [ self::PREPARING, self::SCHEDULED, self::CANCELLED ],
        self::PREPARING => [ self::IN_PROGRESS, self::ON_HOLD ],
        self::IN_PROGRESS => [ self::ON_HOLD, self::COMPLETED, self::PARTIAL, self::NOT_PERFORMED ],
        self::ON_HOLD => [ self::IN_PROGRESS, self::SCHEDULED ],
        self::SCHEDULED => [ self::PREPARING, self::CANCELLED ],
        self::COMPLETED => [],  // Estado final
        self::PARTIAL => [ self::SCHEDULED ],
        self::CANCELLED => [],  // Estado final
        self::NOT_PERFORMED => [ self::SCHEDULED ],
    ];

    /**
     * Verifica se a transição de status é permitida
     */
    public static function canTransitionTo(string $currentStatus, string $newStatus): bool
    {
        return in_array($newStatus, self::ALLOWED_TRANSITIONS[ $currentStatus ] ?? []);
    }

    /**
     * Retorna todos os status possíveis
     * 
     * @return array<int, string>
     */
    public static function getAllStatuses(): array
    {
        return [
            self::PENDING,
            self::SCHEDULING,
            self::PREPARING,
            self::IN_PROGRESS,
            self::ON_HOLD,
            self::SCHEDULED,
            self::COMPLETED,
            self::PARTIAL,
            self::CANCELLED,
            self::NOT_PERFORMED,
        ];
    }

    /**
     * Retorna as transições permitidas para um status
     * 
     * @param string $status Status atual
     * @return array<int, string>
     */
    public static function getAllowedTransitions(string $status): array
    {
        return self::ALLOWED_TRANSITIONS[ $status ] ?? [];
    }

    /**
     * Verifica se o status é final
     */
    public static function isFinalStatus(string $status): bool
    {
        return empty(self::ALLOWED_TRANSITIONS[ $status ]);
    }

}
