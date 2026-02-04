<?php

declare(strict_types=1);

namespace App\Services\Traits;

use App\Enums\OperationStatus;
use App\Support\ServiceResult;

/**
 * Trait ServiceValidationHelpers
 *
 * Contém helpers de validação básica para serem utilizados por classes
 * que estendem AbstractBaseService.
 *
 * Requer que a classe implemente os métodos error() e success() do ServiceResult.
 */
trait ServiceValidationHelpers
{
    /**
     * Define o método error, que deve ser implementado na classe abstrata
     * (AbstractBaseService) ou na classe que usar este Trait.
     */
    abstract protected function error(OperationStatus|string $status, string $message = '', mixed $data = null, ?\Exception $exception = null): ServiceResult;

    /**
     * Define o método success, que deve ser implementado na classe abstrata.
     */
    abstract protected function success(mixed $data = null, string $message = ''): ServiceResult;

    // --------------------------------------------------------------------------
    // MÉTODOS DE VALIDAÇÃO
    // --------------------------------------------------------------------------

    /**
     * Valida campos obrigatórios nos dados.
     *
     * Retorna um ServiceResult de sucesso se todos os campos existirem e não estiverem vazios.
     *
     * @param  array  $data  Dados a validar
     * @param  array  $requiredFields  Campos obrigatórios
     * @return ServiceResult Resultado da validação
     */
    protected function validateRequired(array $data, array $requiredFields): ServiceResult
    {
        $errors = [];
        foreach ($requiredFields as $field) {
            // Verifica se a chave não existe OU se é uma string vazia/null
            if (! isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                // Para melhor UX, podemos retornar quais campos falharam no array de dados
                $errors[$field] = "O campo '{$field}' é obrigatório.";
            }
        }

        if (! empty($errors)) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'Campos obrigatórios ausentes ou vazios.',
                $errors,
            );
        }

        return $this->success(null, 'Validação de campos obrigatórios OK');
    }

    /**
     * Valida o comprimento de um campo de string.
     *
     * @param  string  $value  Valor a validar
     * @param  int  $min  Comprimento mínimo
     * @param  int  $max  Comprimento máximo
     * @param  string  $field  Nome do campo para mensagem de erro
     */
    protected function validateLength(string $value, int $min, int $max, string $field): ServiceResult
    {
        $length = strlen($value);

        if ($length < $min) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                "O campo '{$field}' deve ter pelo menos {$min} caracteres.",
                ['field' => $field, 'min' => $min],
            );
        }

        if ($length > $max) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                "O campo '{$field}' deve ter no máximo {$max} caracteres.",
                ['field' => $field, 'max' => $max],
            );
        }

        return $this->success();
    }

    /**
     * Valida se um valor é um número inteiro válido dentro de um intervalo.
     *
     * @param  mixed  $value  Valor a validar
     * @param  int  $min  Valor mínimo (inclusive)
     * @param  int  $max  Valor máximo (inclusive)
     * @param  string  $field  Nome do campo
     */
    protected function validateIntegerRange(mixed $value, int $min, int $max, string $field): ServiceResult
    {
        if (! is_numeric($value) || ! is_int((int) $value) || (string) (int) $value !== (string) $value) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                "O campo '{$field}' deve ser um número inteiro válido.",
                ['field' => $field],
            );
        }

        $intValue = (int) $value;

        if ($intValue < $min || $intValue > $max) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                "O campo '{$field}' deve estar entre {$min} e {$max}.",
                ['field' => $field, 'min' => $min, 'max' => $max],
            );
        }

        return $this->success();
    }
}
