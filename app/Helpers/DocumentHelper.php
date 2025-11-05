<?php

namespace App\Helpers;

class DocumentHelper
{
    /**
     * Limpa número do documento (CNPJ/CPF) removendo formatação para busca parcial
     */
    public static function cleanPartial(?string $documentNumber, int $minLength = 2): ?string
    {
        if (empty($documentNumber)) {
            return null;
        }

        // Remove all non-digit characters (points, hyphens, slashes)
        $cleaned = preg_replace('/[^0-9]/', '', $documentNumber);

        // Return if has minimum length
        if (strlen($cleaned) >= $minLength) {
            return $cleaned;
        }

        // Return null if too short
        return null;
    }
}