<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    /**
     * Formata data ou retorna valor padrão se inválida.
     */
    public static function formatDateOrDefault( $date, string $format = 'd/m/Y', string $default = 'Não informado' ): string
    {
        if ( empty( $date ) ) {
            return $default;
        }

        try {
            return Carbon::parse( $date )->format( $format );
        } catch ( \Exception $e ) {
            return $default;
        }
    }

    /**
     * Retorna mês/ano em PT-BR (ex.: Outubro/2025).
     */
    public static function monthYearPt( $date ): string
    {
        return Carbon::parse( $date )
            ->locale( 'pt_BR' )
            ->translatedFormat( 'F/Y' );
    }

    /**
     * Diferença de tempo em relação a agora (ex.: "há 2 dias").
     */
    public static function timeDiff( $datetime ): string
    {
        return Carbon::parse( $datetime )->diffForHumans();
    }

}
