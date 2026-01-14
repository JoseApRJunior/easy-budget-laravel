<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Formata valor monetário em BRL.
     */
    public static function format($value, int $decimals = 2, bool $withSymbol = false): string
    {
        $prefix = $withSymbol ? 'R$ ' : '';

        return $prefix.number_format((float) $value, $decimals, ',', '.');
    }

    /**
     * Remove formatação de moeda e retorna float.
     */
    public static function unformat($value): float
    {
        if (empty($value)) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove R$ e outros caracteres não numéricos, exceto separadores
        $clean = preg_replace('/[^\d,.-]/', '', (string) $value);

        // Se houver vírgula, tratamos como formato brasileiro (1.234,56)
        if (str_contains($clean, ',')) {
            $clean = str_replace('.', '', $clean); // Remove pontos de milhar
            $clean = str_replace(',', '.', $clean); // Converte vírgula decimal em ponto
        }

        return (float) $clean;
    }
}
