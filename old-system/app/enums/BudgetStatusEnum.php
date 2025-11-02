<?php

namespace app\enums;

final class BudgetStatus
{
    public const DRAFT       = 'DRAFT';
    public const PENDING     = 'PENDING';
    public const APPROVED    = 'APPROVED';
    public const IN_PROGRESS = 'IN_PROGRESS';
    public const COMPLETED   = 'COMPLETED';
    public const REJECTED    = 'REJECTED';
    public const CANCELLED   = 'CANCELLED';
    public const EXPIRED     = 'EXPIRED';

    private const ALLOWED_TRANSITIONS = [
        self::DRAFT       => [
            self::PENDING,
            self::CANCELLED,
        ],
        self::PENDING     => [
            self::APPROVED,
            self::REJECTED,
            self::EXPIRED,
            self::CANCELLED,
        ],
        self::APPROVED    => [
            self::IN_PROGRESS,
            self::CANCELLED,
        ],
        self::IN_PROGRESS => [
            self::COMPLETED,
            self::CANCELLED,
        ],
        self::COMPLETED   => [], // Estado final
        self::REJECTED    => [], // Estado final
        self::CANCELLED   => [], // Estado final
        self::EXPIRED     => [
            self::DRAFT, // Permite criar novo rascunho
        ],
    ];

    /**
     * Verifica se a transição de status é permitida
     */
    public static function canTransitionTo( string $currentStatus, string $newStatus ): bool
    {
        return in_array( $newStatus, self::ALLOWED_TRANSITIONS[ $currentStatus ] ?? [] );
    }

    /**
     * Retorna todos os status possíveis
     */
    public static function getAllStatuses(): array
    {
        return [
            self::DRAFT,
            self::PENDING,
            self::APPROVED,
            self::IN_PROGRESS,
            self::COMPLETED,
            self::REJECTED,
            self::CANCELLED,
            self::EXPIRED,
        ];
    }

    /**
     * Retorna as transições permitidas para um status
     */
    public static function getAllowedTransitions( string $status ): array
    {
        return self::ALLOWED_TRANSITIONS[ $status ] ?? [];
    }

    /**
     * Verifica se o status é final
     */
    public static function isFinalStatus( string $status ): bool
    {
        return empty( self::ALLOWED_TRANSITIONS[ $status ] );
    }

    /**
     * Verifica se o status permite edição do orçamento
     */
    public static function isEditable( string $status ): bool
    {
        return in_array( $status, [ self::DRAFT ] );
    }

    /**
     * Verifica se o status está aguardando ação do cliente
     */
    public static function isPendingCustomerAction( string $status ): bool
    {
        return in_array( $status, [ self::PENDING ] );
    }

    /**
     * Verifica se o orçamento está ativo (em andamento ou aprovado)
     */
    public static function isActive( string $status ): bool
    {
        return in_array( $status, [ self::APPROVED, self::IN_PROGRESS ] );
    }

    /**
     * Verifica se o orçamento foi finalizado (completo ou cancelado)
     */
    public static function isFinished( string $status ): bool
    {
        return in_array( $status, [ self::COMPLETED, self::REJECTED, self::CANCELLED, self::EXPIRED ] );
    }

    /**
     * Retorna os status que representam conclusão negativa
     */
    public static function getNegativeStatuses(): array
    {
        return [ self::REJECTED, self::CANCELLED, self::EXPIRED ];
    }

    /**
     * Retorna os status que representam conclusão positiva
     */
    public static function getPositiveStatuses(): array
    {
        return [ self::COMPLETED ];
    }

}
