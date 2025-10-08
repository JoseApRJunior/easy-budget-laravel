<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface para repositórios que utilizam ORM
 */
interface EntityORMInterface
{
    /**
     * Retorna o modelo da entidade
     */
    public function getModel(): string;

    /**
     * Retorna o nome da tabela
     */
    public function getTable(): string;
}
