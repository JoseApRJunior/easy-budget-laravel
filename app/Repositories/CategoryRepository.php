<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Abstracts\AbstractGlobalRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para gerenciamento de categorias.
 *
 * Estende AbstractGlobalRepository para operações globais
 * (categorias são compartilhadas entre tenants).
 */
class CategoryRepository extends AbstractGlobalRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Category();
    }

    /**
     * Busca categoria por slug.
     *
     * @param string $slug Slug da categoria
     * @return Category|null Categoria encontrada
     */
    public function findBySlug( string $slug ): ?Category
    {
        return $this->model->where( 'slug', $slug )->first();
    }

    /**
     * Lista categorias ativas.
     *
     * @param array<string, string>|null $orderBy Ordenação
     * @return Collection<Category> Categorias ativas
     */
    public function findActive( ?array $orderBy = null ): Collection
    {
        return $this->getAllGlobal(
            [ 'status' => 'active' ],
            $orderBy,
        );
    }

    /**
     * Busca categorias ordenadas por nome.
     *
     * @param string $direction Direção da ordenação (asc/desc)
     * @return Collection<Category> Categorias ordenadas
     */
    public function findOrderedByName( string $direction = 'asc' ): Collection
    {
        return $this->getAllGlobal(
            [],
            [ 'name' => $direction ],
        );
    }

}
