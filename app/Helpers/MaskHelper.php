<?php

namespace App\Helpers;

class MaskHelper
{
    /**
     * Formata CPF com máscara: 000.000.000-00
     */
    public static function formatCPF( ?string $cpf ): string
    {
        if ( empty( $cpf ) ) {
            return '-';
        }

        // Remove caracteres não numéricos
        $cpf = preg_replace( '/[^0-9]/', '', $cpf );

        // Aplica máscara
        if ( strlen( $cpf ) === 11 ) {
            return preg_replace( '/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf );
        }

        return $cpf;
    }

    /**
     * Formata CNPJ com máscara: 00.000.000/0000-00
     */
    public static function formatCNPJ( ?string $cnpj ): string
    {
        if ( empty( $cnpj ) ) {
            return '-';
        }

        // Remove caracteres não numéricos
        $cnpj = preg_replace( '/[^0-9]/', '', $cnpj );

        // Aplica máscara
        if ( strlen( $cnpj ) === 14 ) {
            return preg_replace( '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj );
        }

        return $cnpj;
    }

    /**
     * Formata telefone com máscara: (00) 00000-0000
     */
    public static function formatPhone( ?string $phone ): string
    {
        if ( empty( $phone ) ) {
            return '-';
        }

        // Remove caracteres não numéricos
        $phone = preg_replace( '/[^0-9]/', '', $phone );

        // Aplica máscara baseada no tamanho
        if ( strlen( $phone ) === 10 ) {
            // Telefone fixo: (00) 0000-0000
            return preg_replace( '/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone );
        } elseif ( strlen( $phone ) === 11 ) {
            // Celular: (00) 00000-0000
            return preg_replace( '/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone );
        }

        return $phone;
    }

    /**
     * Formata CEP com máscara: 00000-000
     */
    public static function formatCEP( ?string $cep ): string
    {
        if ( empty( $cep ) ) {
            return '-';
        }

        // Remove caracteres não numéricos
        $cep = preg_replace( '/[^0-9]/', '', $cep );

        // Aplica máscara
        if ( strlen( $cep ) === 8 ) {
            return preg_replace( '/(\d{5})(\d{3})/', '$1-$2', $cep );
        }

        return $cep;
    }

    /**
     * Remove máscara de CPF/CNPJ
     */
    public static function removeCpfCnpjMask( string $document ): string
    {
        return preg_replace( '/[^0-9]/', '', $document );
    }

    /**
     * Remove máscara de telefone
     */
    public static function removePhoneMask( string $phone ): string
    {
        return preg_replace( '/[^0-9]/', '', $phone );
    }

    /**
     * Remove máscara de CEP
     */
    public static function removeCEPMask( string $cep ): string
    {
        return preg_replace( '/[^0-9]/', '', $cep );
    }

    /**
     * Valida CPF
     */
    public static function validateCPF( string $cpf ): bool
    {
        $cpf = preg_replace( '/[^0-9]/', '', $cpf );

        if ( strlen( $cpf ) != 11 ) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if ( preg_match( '/^(\d)\1{10}$/', $cpf ) ) {
            return false;
        }

        // Calcula primeiro dígito verificador
        $sum = 0;
        for ( $i = 0; $i < 9; $i++ ) {
            $sum += $cpf[ $i ] * ( 10 - $i );
        }
        $remainder = $sum % 11;
        $digit1    = ( $remainder < 2 ) ? 0 : 11 - $remainder;

        // Calcula segundo dígito verificador
        $sum = 0;
        for ( $i = 0; $i < 10; $i++ ) {
            $sum += $cpf[ $i ] * ( 11 - $i );
        }
        $remainder = $sum % 11;
        $digit2    = ( $remainder < 2 ) ? 0 : 11 - $remainder;

        return $cpf[ 9 ] == $digit1 && $cpf[ 10 ] == $digit2;
    }

    /**
     * Valida CNPJ
     */
    public static function validateCNPJ( string $cnpj ): bool
    {
        $cnpj = preg_replace( '/[^0-9]/', '', $cnpj );

        if ( strlen( $cnpj ) != 14 ) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if ( preg_match( '/^(\d)\1{13}$/', $cnpj ) ) {
            return false;
        }

        // Calcula primeiro dígito verificador
        $sum      = 0;
        $weights1 = [ 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ];
        for ( $i = 0; $i < 12; $i++ ) {
            $sum += $cnpj[ $i ] * $weights1[ $i ];
        }
        $remainder = $sum % 11;
        $digit1    = ( $remainder < 2 ) ? 0 : 11 - $remainder;

        // Calcula segundo dígito verificador
        $sum      = 0;
        $weights2 = [ 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ];
        for ( $i = 0; $i < 13; $i++ ) {
            $sum += $cnpj[ $i ] * $weights2[ $i ];
        }
        $remainder = $sum % 11;
        $digit2    = ( $remainder < 2 ) ? 0 : 11 - $remainder;

        return $cnpj[ 12 ] == $digit1 && $cnpj[ 13 ] == $digit2;
    }

}
