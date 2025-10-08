<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface para serviços que suportam ativação/desativação
 */
interface ActivatableInterface
{
    /**
     * Ativa uma entidade
     */
    public function activate( int $id ): bool;

    /**
     * Desativa uma entidade
     */
    public function deactivate( int $id ): bool;

    /**
     * Verifica se uma entidade está ativa
     */
    public function isActive( int $id ): bool;
}
