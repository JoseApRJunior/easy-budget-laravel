<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para operações de usuário tenant-aware
 *
 * Implementa métodos específicos para gerenciamento de usuários
 * com isolamento automático por tenant_id
 */
class UserRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new User();
    }

    /**
     * Encontra usuário por email independentemente do tenant (busca global).
     *
     * @param string $email Email do usuário
     * @return User|null Usuário encontrado ou null
     */
    public function findByEmail( string $email ): ?User
    {
        return $this->model->withoutTenant()->where( 'email', $email )->first();
    }

    /**
     * Encontra usuários ativos dentro do tenant atual.
     *
     * @return Collection<User> Coleção de usuários ativos
     */
    public function findActive(): Collection
    {
        return $this->getAllByTenant( [ 'is_active' => true ] );
    }

    /**
     * Valida se email é único dentro do tenant atual.
     *
     * @param string $email Email a ser verificado
     * @param int|null $excludeId ID do usuário a ser excluído da verificação
     * @return bool True se é único, false caso contrário
     */
    public function isEmailUnique( string $email, ?int $excludeId = null ): bool
    {
        return $this->isUniqueInTenant( 'email', $email, $excludeId );
    }

    /**
     * Conta administradores dentro do tenant atual.
     *
     * @return int Número de administradores
     */
    public function countAdmins(): int
    {
        return $this->countByTenant( [ 'role' => 'admin' ] );
    }

    /**
     * Verifica se slug existe dentro do tenant atual.
     *
     * @param string $slug Slug a ser verificado
     * @param int|null $excludeId ID do usuário a ser excluído da verificação
     * @return bool True se existe, false caso contrário
     */
    public function existsBySlug( string $slug, ?int $excludeId = null ): bool
    {
        return !$this->isUniqueInTenant( 'slug', $slug, $excludeId );
    }

    /**
     * Encontra o primeiro usuário dentro do tenant atual.
     *
     * @return User|null Primeiro usuário ou null
     */
    public function first(): ?User
    {
        return $this->model->first();
    }

    /**
     * Encontra o último usuário dentro do tenant atual.
     *
     * @return User|null Último usuário ou null
     */
    public function last(): ?User
    {
        return $this->model->latest()->first();
    }

}
