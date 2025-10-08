<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface para serviços que suportam paginação
 */
interface PaginatableInterface
{
    /**
     * Retorna dados paginados
     */
    public function paginate( int $perPage = 15, array $filters = [] ): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Retorna cursor paginator para grandes datasets
     */
    public function cursorPaginate( int $perPage = 15, array $filters = [] ): \Illuminate\Contracts\Pagination\CursorPaginator;
}
