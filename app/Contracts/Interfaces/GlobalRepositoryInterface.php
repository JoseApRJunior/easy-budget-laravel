<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface para repositórios globais (sem tenant)
 */
interface GlobalRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca registros sem considerar tenant
     */
    public function findGlobal( int $id ): ?\Illuminate\Database\Eloquent\Model;

    /**
     * Lista todos os registros globais
     */
    public function listGlobal(): \Illuminate\Database\Eloquent\Collection;
}
