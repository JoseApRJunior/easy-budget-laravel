<?php

declare(strict_types=1);

namespace App\Repositories;

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
     * Busca um tenant pelo nome.
     *
     * @param string $name Nome do tenant
     * @return Tenant|null Tenant encontrado ou null
     */
    public function findByName( string $name ): ?Tenant
    {
        return $this->model->where( 'name', $name )->first();
    }

    /**
     * Verifica se existe um tenant com o nome especificado.
     *
     * @param string $name Nome do tenant
     * @return bool True se existe
     */
    public function existsByName( string $name ): bool
    {
        return $this->model->where( 'name', $name )->exists();
    }

    /**
     * Busca tenants ativos.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Tenant>
     */
    public function findActive()
    {
        return $this->model->where( 'is_active', true )->get();
    }

    /**
     * Cria um novo tenant.
     *
     * @param array<string, mixed> $data Dados do tenant
     * @return Tenant Tenant criado
     */
    public function createTenant( array $data ): Tenant
    {
        return $this->create( $data );
    }

}
