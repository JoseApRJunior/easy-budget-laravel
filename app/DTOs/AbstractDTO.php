<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Classe base abstrata para todos os DTOs do sistema.
 * Fornece métodos utilitários para conversão e manipulação de dados.
 */
abstract readonly class AbstractDTO
{
    public function toArray(): array
    {
        // Retorna todas as propriedades públicas
        return get_object_vars($this);
    }

    /**
     * Retorna apenas os campos preenchidos.
     * Útil para o método update() do Eloquent.
     */
    public function toArrayWithoutNulls(): array
    {
        return array_filter($this->toArray(), fn($value) => $value !== null);
    }
}
