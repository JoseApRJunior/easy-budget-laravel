<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Tenant\TenantDTO;
use App\Models\Tenant;
use App\Repositories\Abstracts\AbstractGlobalRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para gerenciar tenants (não tenant-aware)
 *
 * Estende AbstractGlobalRepository para operações globais
 * de gerenciamento de tenants, como criação, busca e validação
 */
class TenantRepository extends AbstractGlobalRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Tenant();
    }

    /**
     * Cria um novo tenant a partir de um DTO.
     */
    public function createFromDTO(TenantDTO $dto): Tenant
    {
        return $this->model->newQuery()->create($dto->toArray());
    }

    /**
     * Atualiza um tenant a partir de um DTO.
     */
    public function updateFromDTO(int $id, TenantDTO $dto): bool
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->update($dto->toArray()) > 0;
    }

    /**
     * Busca um tenant pelo nome.
     *
     * @param string $name Nome do tenant
     * @return Tenant|null Tenant encontrado ou null
     */
    public function findByName( string $name ): ?Tenant
    {
        return $this->model->newQuery()->where( 'name', $name )->first();
    }

    /**
     * Verifica se existe um tenant com o nome especificado.
     *
     * @param string $name Nome do tenant
     * @return bool True se existe
     */
    public function existsByName( string $name ): bool
    {
        return $this->model->newQuery()->where( 'name', $name )->exists();
    }

    /**
     * Busca tenants ativos.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Tenant>
     */
    public function findActive()
    {
        return $this->model->newQuery()->where( 'is_active', true )->get();
    }

}
