<?php

declare(strict_types=1);

namespace App\DTOs;

use ReflectionClass;

/**
 * Classe base abstrata para todos os DTOs do sistema.
 * Fornece métodos utilitários para conversão e manipulação de dados.
 */
abstract readonly class AbstractDTO
{
    /**
     * Converte o DTO para um array com todas as propriedades.
     * Recursivo: converte também DTOs aninhados e arrays de DTOs.
     */
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();
        $array = [];

        foreach ($properties as $property) {
            $value = $property->getValue($this);

            if ($value instanceof AbstractDTO) {
                $array[$property->getName()] = $value->toArray();
            } elseif ($value instanceof \BackedEnum) {
                $array[$property->getName()] = $value->value;
            } elseif (is_array($value)) {
                $array[$property->getName()] = array_map(function ($item) {
                    if ($item instanceof AbstractDTO) {
                        return $item->toArray();
                    }
                    if ($item instanceof \BackedEnum) {
                        return $item->value;
                    }
                    return $item;
                }, $value);
            } else {
                $array[$property->getName()] = $value;
            }
        }

        return $array;
    }

    /**
     * Retorna apenas os campos preenchidos (não nulos).
     * Útil para operações de update parcial no Eloquent.
     */
    public function toArrayWithoutNulls(): array
    {
        return array_filter($this->toArray(), fn ($value) => $value !== null);
    }

    /**
     * Converte o DTO para um array formatado para persistência no banco de dados.
     * Pode ser sobrescrito em DTOs específicos para mapear nomes de campos.
     */
    public function toDatabaseArray(): array
    {
        return $this->toArray();
    }

    /**
     * Formata um nome para Case Title (Primeira Letra Maiúscula).
     */
    protected static function formatTitle(string $value): string
    {
        return mb_convert_case(trim($value), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Remove caracteres não numéricos de uma string (CPF, CNPJ, CEP).
     */
    protected static function sanitizeNumbers(?string $value): ?string
    {
        return $value ? preg_replace('/[^0-9]/', '', $value) : null;
    }

    /**
     * Mapeia um booleano de forma flexível (aceita null, strings 'true'/'false', 0/1).
     */
    protected static function mapBoolean($value, bool $default = true): bool
    {
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Método auxiliar para instanciar o DTO a partir de um array.
     * Usa Reflection para mapear apenas os argumentos existentes no construtor.
     * Ignora chaves extras no array de entrada (previne erros).
     *
     * @return static
     */
    public static function fromArray(array $data): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            // @phpstan-ignore-next-line
            return new static;
        }

        $params = $constructor->getParameters();
        $args = [];

        foreach ($params as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $data)) {
                $args[$name] = $data[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[$name] = $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                $args[$name] = null;
            }
            // Se for obrigatório e não estiver presente, o PHP lançará ArgumentCountError na instanciação
        }

        // @phpstan-ignore-next-line
        return new static(...$args);
    }
}
