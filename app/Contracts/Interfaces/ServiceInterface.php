<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface base para serviços
 */
interface ServiceInterface
{
    /**
     * Cria uma nova entidade
     */
    public function create( array $data ): \App\Support\ServiceResult;

    /**
     * Atualiza uma entidade existente
     */
    public function update( int $id, array $data ): \App\Support\ServiceResult;

    /**
     * Remove uma entidade
     */
    public function delete( int $id ): \App\Support\ServiceResult;

    /**
     * Busca uma entidade por ID
     */
    public function find( int $id ): ?\Illuminate\Database\Eloquent\Model;

    /**
     * Lista todas as entidades
     */
    public function list( array $filters = [] ): \Illuminate\Database\Eloquent\Collection;
}
