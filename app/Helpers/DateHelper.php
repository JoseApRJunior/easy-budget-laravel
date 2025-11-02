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

    /**
     * Converte data de nascimento do formato brasileiro para Y-m-d
     */
    public static function parseBirthDate( ?string $birthDate ): ?string
    {
        if ( empty( $birthDate ) ) {
            return null;
        }

        // Se já estiver no formato Y-m-d, retorna como está
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $birthDate ) ) {
            return $birthDate;
        }

        // Converte do formato DD/MM/YYYY para Y-m-d
        if ( preg_match( '/^(\d{2})\/(\d{2})\/(\d{4})$/', $birthDate, $matches ) ) {
            $day   = $matches[ 1 ];
            $month = $matches[ 2 ];
            $year  = $matches[ 3 ];

            // Valida se é uma data válida
            if ( checkdate( (int) $month, (int) $day, (int) $year ) ) {
                return sprintf( '%04d-%02d-%02d', $year, $month, $day );
            }
        }

        // Se não conseguir converter, retorna null para evitar erro
        return null;
    }

}
