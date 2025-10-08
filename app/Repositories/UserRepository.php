<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositório para operações de usuário tenant-aware
 *
 * Implementa métodos específicos para gerenciamento de usuários
 * com isolamento automático por tenant_id
 */
class UserRepository extends AbstractTenantRepository
{
    protected string $modelClass = User::class;

    /**
     * Cria uma nova instância do modelo User
     *
     * @return User
     */
    protected function makeModel(): User
    {
        return new User();
    }

    /**
     * Encontra usuário por email e tenant
     *
     * @param string $email Email do usuário
     * @param int $tenantId ID do tenant
     * @return User|null Usuário encontrado ou null
     */
    public function findByEmailAndTenant( string $email, int $tenantId ): ?User
    {
        return $this->findOneByCriteriaAndTenant( [ 'email' => $email ], $tenantId );
    }

    /**
     * Encontra usuários ativos por tenant
     *
     * @param int $tenantId ID do tenant
     * @return Collection Coleção de usuários ativos
     */
    public function findActiveByTenant( int $tenantId ): Collection
    {
        return $this->findByCriteriaAndTenant( [ 'is_active' => true ], $tenantId );
    }

    /**
     * Valida se email é único no tenant
     *
     * @param string $email Email a ser verificado
     * @param int $tenantId ID do tenant
     * @param int|null $excludeId ID do usuário a ser excluído da verificação
     * @return bool True se é único, false caso contrário
     */
    public function validateUniqueEmailInTenant( string $email, int $tenantId, ?int $excludeId = null ): bool
    {
        return $this->validateUniqueInTenant( 'email', $email, $tenantId, $excludeId );
    }

    /**
     * Conta administradores por tenant
     *
     * @param int $tenantId ID do tenant
     * @return int Número de administradores
     */
    public function countAdminsByTenantId( int $tenantId ): int
    {
        return $this->newQuery()
            ->where( 'tenant_id', $tenantId )
            ->where( 'role', 'admin' )
            ->count();
    }

    /**
     * Verifica se slug existe por tenant
     *
     * @param string $slug Slug a ser verificado
     * @param int $tenantId ID do tenant
     * @param int|null $excludeId ID do usuário a ser excluído da verificação
     * @return bool True se existe, false caso contrário
     */
    public function existsBySlugAndTenantId( string $slug, int $tenantId, ?int $excludeId = null ): bool
    {
        return !$this->validateUniqueInTenant( 'slug', $slug, $tenantId, $excludeId );
    }

    /**
     * Encontra o primeiro registro
     *
     * @return User|null Primeiro registro ou null
     */
    public function first(): ?User
    {
        return $this->newQuery()->first();
    }

    /**
     * Encontra o último registro
     *
     * @return User|null Último registro ou null
     */
    public function last(): ?User
    {
        return $this->newQuery()->last();
    }

}
