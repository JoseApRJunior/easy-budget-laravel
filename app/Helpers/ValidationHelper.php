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
            return false;
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
            return false;
        }

        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $length = strlen($cnpj) - 2;
        $numbers = substr($cnpj, 0, $length);
        $digits = substr($cnpj, $length);
        $sum = 0;
        $pos = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += $numbers[$length - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }

        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;

        if ($result != $digits[0]) {
            return false;
        }

        $length = $length + 1;
        $numbers = substr($cnpj, 0, $length);
        $sum = 0;
        $pos = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += $numbers[$length - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }

        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;

        return $result == $digits[1];
    }

    /**
     * Valida CEP.
     */
    public static function isValidCep(?string $cep): bool
    {
        if (empty($cep)) {
            return false;
        }

        return preg_match('/^\d{5}-?\d{3}$/', $cep) === 1;
    }

    /**
     * Valida email.
     */
    public static function isValidEmail(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida telefone brasileiro.
     */
    public static function isValidPhone(?string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Aceita: (11) 98888-8888, (11) 8888-8888, 11988888888, 1188888888
        return strlen($phone) >= 10 && strlen($phone) <= 11;
    }

    /**
     * Valida data de nascimento (deve ser anterior a hoje e pessoa maior de 18 anos).
     */
    public static function isValidBirthDate(?string $birthDate, int $minAge = 18): bool
    {
        if (empty($birthDate)) {
            return false;
        }

        try {
            // Usa DateHelper para converter a data para formato padrão
            $parsedDate = DateHelper::parseBirthDate($birthDate);
            if ($parsedDate === null) {
                return false;
            }

            $date = \Carbon\Carbon::parse($parsedDate);
            $today = \Carbon\Carbon::today();

            // Deve ser anterior a hoje
            if ($date >= $today) {
                return false;
            }

            // Verifica idade mínima (data de nascimento até hoje)
            $age = $date->diffInYears($today);

            return $age >= $minAge;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Valida URL.
     */
    public static function isValidUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
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
     * Formata telefone brasileiro.
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
