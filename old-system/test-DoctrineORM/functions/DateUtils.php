<?php

namespace core\functions;

use DateInterval;
use DateTime;

class DateUtils
{
    /**
     * Retorna a data/hora atual
     */
    public static function now(): DateTime
    {
        return new DateTime( 'now' );
    }

    /**
     * Formata uma data
     */
    public static function format( ?DateTime $date, string $format = 'Y-m-d H:i:s' ): string
    {
        return $date ? $date->format( $format ) : '';
    }

    /**
     * Formata data no padrão brasileiro
     */
    public static function formatBR( DateTime $date ): string
    {
        return $date->format( 'd/m/Y H:i:s' );
    }

    /**
     * Cria DateTime a partir de uma string
     */
    public static function createFromFormat( string $date, string $format = 'Y-m-d H:i:s' ): ?DateTime
    {
        $dateTime = DateTime::createFromFormat( $format, $date );

        return $dateTime ?: null;
    }

    /**
     * Verifica se uma data é válida
     */
    public static function isValid( string $date, string $format = 'Y-m-d H:i:s' ): bool
    {
        $dateTime = DateTime::createFromFormat( $format, $date );

        return $dateTime && $dateTime->format( $format ) === $date;
    }

    /**
     * Adiciona/subtrai dias
     */
    public static function addDays( DateTime $date, int $days ): DateTime
    {
        return ( clone $date )->modify( ( $days >= 0 ? '+' : '' ) . "$days days" );
    }

    /**
     * Calcula diferença em dias
     */
    /**
     * Calcula a diferença em dias entre duas datas.
     *
     * @param DateTime $date1 A primeira data.
     * @param DateTime $date2 A segunda data.
     * @return int O número de dias de diferença.
     */
    public static function diffInDays( DateTime $date1, DateTime $date2 ): int
    {
        $interval = $date1->diff( $date2 );

        // A propriedade 'days' pode ser 'false' em alguns casos.
        // O operador elvis (?:) garante que, se for 'false', retornará 0.
        return $interval->days ?: 0;
    }

    /**
     * Verifica se é dia útil
     */
    public static function isBusinessDay( DateTime $date ): bool
    {
        return !in_array( $date->format( 'N' ), [ '6', '7' ] );
    }

    /**
     * Início e fim do dia
     */
    public static function startOfDay( DateTime $date ): DateTime
    {
        return ( clone $date )->setTime( 0, 0, 0 );
    }

    public static function endOfDay( DateTime $date ): DateTime
    {
        return ( clone $date )->setTime( 23, 59, 59 );
    }

    /**
     * Início e fim do mês
     */
    public static function startOfMonth( DateTime $date ): DateTime
    {
        return ( clone $date )
            ->modify( 'first day of this month' )
            ->setTime( 0, 0, 0 );
    }

    public static function endOfMonth( DateTime $date ): DateTime
    {
        return ( clone $date )
            ->modify( 'last day of this month' )
            ->setTime( 23, 59, 59 );
    }

    /**
     * Verifica se duas datas são iguais
     */
    public static function isSameDay( DateTime $date1, DateTime $date2 ): bool
    {
        return $date1->format( 'Y-m-d' ) === $date2->format( 'Y-m-d' );
    }

    /**
     * Verifica se é data futura/passada
     */
    public static function isFuture( DateTime $date ): bool
    {
        return $date > self::now();
    }

    public static function isPast( DateTime $date ): bool
    {
        return $date < self::now();
    }

    /**
     * Adiciona/subtrai horas
     */
    public static function addHours( DateTime $date, int $hours ): DateTime
    {
        return ( clone $date )->modify( ( $hours >= 0 ? '+' : '' ) . "$hours hours" );
    }

    /**
     * Adiciona/subtrai minutos
     */
    public static function addMinutes( DateTime $date, int $minutes ): DateTime
    {
        return ( clone $date )->modify( ( $minutes >= 0 ? '+' : '' ) . "$minutes minutes" );
    }

    /**
     * Adiciona/subtrai segundos
     */
    public static function addSeconds( DateTime $date, int $seconds ): DateTime
    {
        return ( clone $date )->modify( ( $seconds >= 0 ? '+' : '' ) . "$seconds seconds" );
    }

    /**
     * Subtrai semanas de uma data
     * @param int $weeks Número de semanas a subtrair
     * @return DateTime
     */
    public static function subWeeks( int $weeks = 1 ): DateTime
    {
        return ( new DateTime() )->sub( new DateInterval( "P{$weeks}W" ) );
    }

    /**
     * Retorna a data formatada de X semanas atrás
     * @param int $weeks Número de semanas a subtrair
     * @param string $format Formato da data
     * @return string
     */
    public static function getWeeksAgo( int $weeks = 1, string $format = 'Y-m-d H:i:s' ): string
    {
        return self::format( self::subWeeks( $weeks ), $format );
    }

}