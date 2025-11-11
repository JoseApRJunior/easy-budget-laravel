<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Helper para geração de documentos brasileiros válidos
 *
 * Gera CPF e CNPJ válidos seguindo as regras oficiais da Receita Federal
 * Telefones no formato brasileiro padrão
 */
class DocumentGeneratorHelper
{
    /**
     * Gera um CPF válido usando algoritmo oficial
     */
    public static function generateValidCpf(): string
    {
        do {
            // Gera os 9 primeiros dígitos aleatórios
            $cpf = str_pad( (string) random_int( 111111111, 999999999 ), 9, '0', STR_PAD_LEFT );

            // Calcula os dois dígitos verificadores
            $cpf = self::calculateCpfDigits( $cpf );

        } while (
            in_array( $cpf, [ '00000000000', '11111111111', '22222222222', '33333333333',
                '44444444444', '55555555555', '66666666666', '77777777777',
                '88888888888', '99999999999' ] )
        );

        return $cpf;
    }

    /**
     * Calcula os dígitos verificadores do CPF
     */
    private static function calculateCpfDigits( string $baseCpf ): string
    {
        $weights1 = [ 10, 9, 8, 7, 6, 5, 4, 3, 2 ];
        $weights2 = [ 11, 10, 9, 8, 7, 6, 5, 4, 3, 2 ];

        // Calcula primeiro dígito
        $sum = 0;
        for ( $i = 0; $i < 9; $i++ ) {
            $sum  += $baseCpf[ $i ] * $weights1[ $i ];
        }
        $digit1 = $sum % 11 < 2 ? 0 : 11 - ( $sum % 11 );

        // Calcula segundo dígito
        $sum = 0;
        for ( $i = 0; $i < 10; $i++ ) {
            $sum  += ( $baseCpf[ $i ] ?? $digit1 ) * $weights2[ $i ];
        }
        $digit2 = $sum % 11 < 2 ? 0 : 11 - ( $sum % 11 );

        return $baseCpf . $digit1 . $digit2;
    }

    /**
     * Gera um CNPJ válido usando algoritmo oficial
     */
    public static function generateValidCnpj(): string
    {
        do {
            // Gera os 8 primeiros dígitos aleatórios
            $cnpj = str_pad( (string) random_int( 11111111, 99999999 ), 8, '0', STR_PAD_LEFT );

            // Adiciona os zeros do meio (00)
            $cnpj  .= '00';

            // Calcula os dois dígitos verificadores
            $cnpj = self::calculateCnpjDigits( $cnpj );

        } while (
            in_array( $cnpj, [ '00000000000191', '11111111000191', '22222222000191',
                '33333333000191', '44444444000191', '55555555000191',
                '66666666000191', '77777777000191', '88888888000191',
                '99999999000191' ] )
        );

        return $cnpj;
    }

    /**
     * Calcula os dígitos verificadores do CNPJ
     */
    private static function calculateCnpjDigits( string $baseCnpj ): string
    {
        $weights1 = [ 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ];
        $weights2 = [ 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ];

        // Calcula primeiro dígito
        $sum = 0;
        for ( $i = 0; $i < 12; $i++ ) {
            $sum  += $baseCnpj[ $i ] * $weights1[ $i ];
        }
        $digit1 = $sum % 11 < 2 ? 0 : 11 - ( $sum % 11 );

        // Calcula segundo dígito
        $sum = 0;
        for ( $i = 0; $i < 13; $i++ ) {
            $sum  += ( $baseCnpj[ $i ] ?? $digit1 ) * $weights2[ $i ];
        }
        $digit2 = $sum % 11 < 2 ? 0 : 11 - ( $sum % 11 );

        return $baseCnpj . $digit1 . $digit2;
    }

    /**
     * Gera um telefone brasileiro válido
     */
    public static function generateValidPhone(): string
    {
        // Gerar DDDs válidos do Brasil (11-99)
        $ddds = [ '11', '12', '13', '14', '15', '16', '17', '18', '19', '21', '22', '24',
            '27', '28', '31', '32', '33', '34', '35', '37', '38', '41', '42', '43',
            '44', '45', '46', '47', '48', '49', '51', '53', '54', '55', '61', '62',
            '64', '65', '66', '67', '68', '69', '71', '73', '74', '75', '77', '79',
            '81', '87', '88', '89', '91', '92', '93', '94', '95', '96', '97', '98', '99' ];

        $ddd = $ddds[ array_rand( $ddds ) ];

        // Gerar número de telefone (diferentes padrões)
        $patterns = [
            '9' . str_pad( (string) random_int( 1000, 9999 ), 4, '0', STR_PAD_LEFT ), // 9xxxx
            str_pad( (string) random_int( 1000, 9999 ), 4, '0', STR_PAD_LEFT ) . '-' . str_pad( (string) random_int( 1000, 9999 ), 4, '0', STR_PAD_LEFT ) // xxxx-xxxx
        ];

        $number = $patterns[ array_rand( $patterns ) ];

        return "({$ddd}) {$number}";
    }

    /**
     * Formata CPF com máscara
     */
    public static function formatCpf( string $cpf ): string
    {
        return preg_replace( '/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf );
    }

    /**
     * Formata CNPJ com máscara
     */
    public static function formatCnpj( string $cnpj ): string
    {
        return preg_replace( '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj );
    }

    /**
     * Formata telefone com máscara
     */
    public static function formatPhone( string $phone ): string
    {
        $phone = preg_replace( '/\D/', '', $phone );

        if ( strlen( $phone ) === 11 ) {
            return preg_replace( '/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone );
        } elseif ( strlen( $phone ) === 10 ) {
            return preg_replace( '/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone );
        }

        return $phone;
    }

    /**
     * Valida CPF usando algoritmo oficial
     */
    public static function validateCpf( string $cpf ): bool
    {
        $cpf = preg_replace( '/\D/', '', $cpf );

        if ( strlen( $cpf ) !== 11 ) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if ( preg_match( '/^(\d)\1{10}$/', $cpf ) ) {
            return false;
        }

        // Calcula dígitos verificadores
        $weights = [ 10, 9, 8, 7, 6, 5, 4, 3, 2, 1, 0 ];

        $sum = 0;
        for ( $i = 0; $i < 9; $i++ ) {
            $sum  += $cpf[ $i ] * $weights[ $i + 1 ];
        }
        $digit1 = $sum % 11 < 2 ? 0 : 11 - ( $sum % 11 );

        $sum = 0;
        for ( $i = 0; $i < 10; $i++ ) {
            $sum  += $cpf[ $i ] * $weights[ $i ];
        }
        $digit2 = $sum % 11 < 2 ? 0 : 11 - ( $sum % 11 );

        return $cpf[ 9 ] == $digit1 && $cpf[ 10 ] == $digit2;
    }

    /**
     * Valida CNPJ usando algoritmo oficial
     */
    public static function validateCnpj( string $cnpj ): bool
    {
        $cnpj = preg_replace( '/\D/', '', $cnpj );

        if ( strlen( $cnpj ) !== 14 ) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if ( preg_match( '/^(\d)\1{13}$/', $cnpj ) ) {
            return false;
        }

        // Calcula primeiro dígito verificador
        $weights1 = [ 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ];
        $sum      = 0;
        for ( $i = 0; $i < 12; $i++ ) {
            $sum  += $cnpj[ $i ] * $weights1[ $i ];
        }
        $digit1 = $sum % 11 < 2 ? 0 : 11 - ( $sum % 11 );

        // Calcula segundo dígito verificador
        $weights2 = [ 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ];
        $sum      = 0;
        for ( $i = 0; $i < 13; $i++ ) {
            $sum  += ( $cnpj[ $i ] ?? $digit1 ) * $weights2[ $i ];
        }
        $digit2 = $sum % 11 < 2 ? 0 : 11 - ( $sum % 11 );

        return $cnpj[ 12 ] == $digit1 && $cnpj[ 13 ] == $digit2;
    }

}
