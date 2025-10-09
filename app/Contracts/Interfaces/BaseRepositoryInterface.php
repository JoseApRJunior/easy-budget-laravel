<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface base simplificada para repositórios
 *
 * Define apenas métodos CRUD básicos essenciais
 */
interface BaseRepositoryInterface
{
    /**
     * Busca todos os registros
     */
    public function all(): Collection;

    /**
     * Busca registro por ID
     */
    public function find( int $id ): ?Model;

    /**
     * Cria novo registro
     */
    public function create( array $data ): Model;

    /**
     * Atualiza registro existente
     */
    public function update( int $id, array $data ): bool;

    /**
     * Remove registro
     */
    public function delete( int $id ): bool;
}
