<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Classe base abstrata para todos os DTOs do sistema.
 * Fornece métodos utilitários para conversão e manipulação de dados.
 */
abstract readonly class AbstractDTO
{
    /**
     * Converte o DTO para um array com todas as propriedades.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Retorna apenas os campos preenchidos (não nulos).
     * Útil para operações de update parcial no Eloquent.
     */
    public function toArrayWithoutNulls(): array
    {
        return array_filter($this->toArray(), fn($value) => $value !== null);
    }

    /**
     * Método auxiliar para instanciar o DTO a partir de um array.
     * Pode ser sobrescrito se houver necessidade de transformações complexas.
     */
    public static function fromArray(array $data): static
    {
        return new static(...$data);
    }
}
