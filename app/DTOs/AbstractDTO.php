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
            } elseif (is_array($value)) {
                $array[$property->getName()] = array_map(function ($item) {
                    return $item instanceof AbstractDTO ? $item->toArray() : $item;
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
