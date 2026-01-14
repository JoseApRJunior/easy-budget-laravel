<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;
use App\Repositories\Abstracts\AbstractGlobalRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para gerenciamento de papéis (roles).
 *
 * Estende AbstractGlobalRepository para operações globais
 * (papéis são compartilhados entre tenants).
 */
class RoleRepository extends AbstractGlobalRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Role;
    }

    /**
     * Busca papel por nome.
     *
     * @param  string  $name  Nome do papel
     * @return Role|null Papel encontrado
     */
    public function findByName(string $name): ?Role
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * Lista papéis ativos.
     *
     * @return Collection<Role> Papéis ativos
     */
    public function findActive(): Collection
    {
        return $this->getAllGlobal(['status' => 'active']);
    }

    /**
     * Busca papéis ordenados por nome.
     *
     * @param  string  $direction  Direção da ordenação (asc/desc)
     * @return Collection<Role> Papéis ordenados
     */
    public function findOrderedByName(string $direction = 'asc'): Collection
    {
        return $this->getAllGlobal(
            [],
            ['name' => $direction],
        );
    }
}
