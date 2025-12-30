<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    /**
     * Formata data ou retorna valor padrão se inválida.
     */
    public static function formatDateOrDefault($date, string $format = 'd/m/Y', string $default = 'Não informado'): string
    {
        if (empty($date)) {
            return $default;
        }

        try {
            return Carbon::parse($date)->format($format);
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Retorna mês/ano em PT-BR (ex.: Outubro/2025).
     */
    public static function monthYearPt($date): string
    {
        return Carbon::parse($date)
            ->locale('pt_BR')
            ->translatedFormat('F/Y');
    }

    /**
     * Diferença de tempo em relação a agora (ex.: "há 2 dias").
     */
    public static function timeDiff($datetime): string
    {
        return Carbon::parse($datetime)->diffForHumans();
    }

    /**
     * Converte data do formato brasileiro (DD/MM/YYYY) ou ISO (YYYY-MM-DD) para Y-m-d.
     * Suporta delimitadores / e -
     */
    public static function parseBirthDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        // Limpar espaços extras
        $date = trim($date);

        // Se já estiver no formato YYYY-MM-DD, apenas valida e retorna
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            if (checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
                return $date;
            }
        }

        // Converte do formato DD/MM/YYYY ou DD-MM-YYYY para Y-m-d
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $date, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // Tenta usar o Carbon como último recurso para outros formatos
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
