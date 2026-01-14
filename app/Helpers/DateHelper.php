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
     * Converte data de qualquer formato comum (BR ou ISO) para objeto Carbon.
     */
    public static function toCarbon($date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        if ($date instanceof Carbon) {
            return $date;
        }

        // Limpar espaços extras
        $date = trim($date);

        // Se já estiver no formato YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            if (checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
                return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            }
        }

        // Converte do formato DD/MM/YYYY ou DD-MM-YYYY
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $date, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            if (checkdate($month, $day, $year)) {
                return Carbon::createFromFormat('d/m/Y', str_replace('-', '/', $date))->startOfDay();
            }
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Converte data para o formato Y-m-d para persistência ou filtros de banco.
     */
    public static function parseDate(?string $date): ?string
    {
        $carbon = self::toCarbon($date);
        return $carbon ? $carbon->format('Y-m-d') : null;
    }

    /**
     * @deprecated Use parseDate instead
     */
    public static function parseBirthDate(?string $date): ?string
    {
        return self::parseDate($date);
    }
}
