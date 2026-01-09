<?php

declare(strict_types=1);

namespace App\Helpers;

class ValidationHelper
{
    /**
     * Valida CPF.
     */
    public static function isValidCpf(?string $cpf): bool
    {
        if (empty($cpf)) {
            return true;
        }

        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida CNPJ.
     */
    public static function isValidCnpj(?string $cnpj): bool
    {
        if (empty($cnpj)) {
            return true;
        }

        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $base = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($t = 12; $t < 14; $t++) {
            $d = 0;
            $c = 0;
            for ($i = (13 - $t); $i < 13; $i++) {
                $d += $cnpj[$c] * $base[$i];
                $c++;
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cnpj[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida telefone brasileiro.
     */
    public static function isValidPhone(?string $phone): bool
    {
        if (empty($phone)) {
            return true;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Telefones brasileiros têm 10 ou 11 dígitos
        if (strlen($phone) < 10 || strlen($phone) > 11) {
            return false;
        }

        // Não permite números repetidos (ex.: 1111111111)
        if (preg_match('/^(\d)\1+$/', $phone)) {
            return false;
        }

        return true;
    }

    /**
     * Valida CEP brasileiro.
     */
    public static function isValidCep(?string $cep): bool
    {
        if (empty($cep)) {
            return true;
        }

        $cep = preg_replace('/[^0-9]/', '', $cep);

        return strlen($cep) === 8;
    }

    /**
     * Formata CPF (000.000.000-00).
     */
    public static function formatCpf(?string $cpf): ?string
    {
        if (empty($cpf)) {
            return null;
        }

        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($cpf, 0, 3),
            substr($cpf, 3, 3),
            substr($cpf, 6, 3),
            substr($cpf, 9, 2),
        );
    }

    /**
     * Formata CNPJ (00.000.000/0000-00).
     */
    public static function formatCnpj(?string $cnpj): ?string
    {
        if (empty($cnpj)) {
            return null;
        }

        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }

        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($cnpj, 0, 2),
            substr($cnpj, 2, 3),
            substr($cnpj, 5, 3),
            substr($cnpj, 8, 4),
            substr($cnpj, 12, 2),
        );
    }

    /**
     * Formata telefone ( (00) 00000-0000 ou (00) 0000-0000 ).
     */
    public static function formatPhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 11) {
            // (11) 98888-8888
            return sprintf(
                '(%s) %s-%s',
                substr($phone, 0, 2),
                substr($phone, 2, 5),
                substr($phone, 7, 4),
            );
        } elseif (strlen($phone) === 10) {
            // (11) 8888-8888
            return sprintf(
                '(%s) %s-%s',
                substr($phone, 0, 2),
                substr($phone, 2, 4),
                substr($phone, 6, 4),
            );
        }

        return $phone;
    }

    /**
     * Formata CEP (00000-000).
     */
    public static function formatCep(?string $cep): ?string
    {
        if (empty($cep)) {
            return null;
        }

        $cep = preg_replace('/[^0-9]/', '', $cep);

        if (strlen($cep) !== 8) {
            return $cep;
        }

        return sprintf('%s-%s', substr($cep, 0, 5), substr($cep, 5, 3));
    }
}
