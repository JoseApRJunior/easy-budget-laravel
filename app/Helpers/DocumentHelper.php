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

    /**
     * Formata CPF no padrão brasileiro: 000.000.000-00
     */
    public static function formatCpf(?string $cpf): ?string
    {
        if (empty($cpf)) {
            return null;
        }

        // Remove todos os caracteres não numéricos
        $numbers = preg_replace('/[^0-9]/', '', $cpf);

        // Valida se tem exatamente 11 dígitos
        if (strlen($numbers) !== 11) {
            return $numbers; // Retorna sem formatação se não for CPF válido
        }

        // Aplica máscara: 000.000.000-00
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $numbers);
    }

    /**
     * Formata CNPJ no padrão brasileiro: 00.000.000/0000-00
     */
    public static function formatCnpj(?string $cnpj): ?string
    {
        if (empty($cnpj)) {
            return null;
        }

        // Remove todos os caracteres não numéricos
        $numbers = preg_replace('/[^0-9]/', '', $cnpj);

        // Valida se tem exatamente 14 dígitos
        if (strlen($numbers) !== 14) {
            return $numbers; // Retorna sem formatação se não for CNPJ válido
        }

        // Aplica máscara: 00.000.000/0000-00
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $numbers);
    }

    /**
     * Valida CPF
     */
    public static function validateCpf(string $cpf): bool
    {
        // Remove formatação
        $numbers = preg_replace('/[^0-9]/', '', $cpf);

        // Verifica se tem 11 dígitos
        if (strlen($numbers) !== 11) {
            return false;
        }

        // Verifica se todos os dígitos são iguais (ex: 111.111.111-11)
        if (preg_match('/^(\d)\1{10}$/', $numbers)) {
            return false;
        }

        // Validação do primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($numbers[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        if (intval($numbers[9]) !== $digit1) {
            return false;
        }

        // Validação do segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($numbers[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        if (intval($numbers[10]) !== $digit2) {
            return false;
        }

        return true;
    }

    /**
     * Valida CNPJ
     */
    public static function validateCnpj(string $cnpj): bool
    {
        // Remove formatação
        $numbers = preg_replace('/[^0-9]/', '', $cnpj);

        // Verifica se tem 14 dígitos
        if (strlen($numbers) !== 14) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if (preg_match('/^(\d)\1{13}$/', $numbers)) {
            return false;
        }

        // Pesos para o primeiro dígito verificador
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        // Cálculo do primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($numbers[$i]) * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        if (intval($numbers[12]) !== $digit1) {
            return false;
        }

        // Pesos para o segundo dígito verificador
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        // Cálculo do segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += intval($numbers[$i]) * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        if (intval($numbers[13]) !== $digit2) {
            return false;
        }

        return true;
    }

    /**
     * Determina se é PF (CPF) ou PJ (CNPJ) baseado na quantidade de dígitos
     */
    public static function getDocumentType(string $documentNumber): string
    {
        $numbers = preg_replace('/[^0-9]/', '', $documentNumber);

        if (strlen($numbers) === 11) {
            return 'CPF';
        } elseif (strlen($numbers) === 14) {
            return 'CNPJ';
        }

        return 'INVÁLIDO';
    }

    /**
     * Formata telefone para padrão brasileiro: (00) 00000-0000
     */
    public static function formatPhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove todos os caracteres não numéricos
        $numbers = preg_replace('/[^0-9]/', '', $phone);

        // Remove o código do país (55) se presente
        if (substr($numbers, 0, 2) === '55' && strlen($numbers) > 10) {
            $numbers = substr($numbers, 2);
        }

        // Aplica formatação diferente para celulares e fixos
        if (strlen($numbers) <= 10) {
            // Telefone fixo: (00) 0000-0000
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $numbers);
        } else {
            // Celular: (00) 00000-0000
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $numbers);
        }
    }

    /**
     * Formata CEP para padrão brasileiro: 00000-000
     */
    public static function formatCep(?string $cep): ?string
    {
        if (empty($cep)) {
            return null;
        }

        // Remove todos os caracteres não numéricos
        $numbers = preg_replace('/[^0-9]/', '', $cep);

        // Aplica formatação: 00000-000
        if (strlen($numbers) >= 5) {
            return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $numbers);
        }

        return $cep; // Retorna original se não for válido
    }
}
