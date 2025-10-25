<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\UserConfirmationToken;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para tokens de confirmação de usuário.
 *
 * Estende AbstractTenantRepository para operações tenant-scoped
 * pois tokens de confirmação são específicos de cada tenant.
 */
class UserConfirmationTokenRepository extends AbstractTenantRepository implements TenantRepositoryInterface
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new UserConfirmationToken();
    }

    /**
     * Encontra token por token hash dentro do tenant atual.
     *
     * Busca case-insensitive para evitar problemas de compatibilidade
     * entre geração e validação de tokens.
     *
     * @param string $tokenHash
     * @return UserConfirmationToken|null
     */
    public function findByToken( string $tokenHash ): ?UserConfirmationToken
    {
        // Para busca de tokens, usamos o tenant_id do contexto de teste
        $tenantId = config( 'tenant.testing_id' ) ?? 1;

        return $this->model->withoutGlobalScope( \App\Models\Traits\TenantScope::class)
            ->where( 'tenant_id', $tenantId )
            ->whereRaw( 'LOWER(token) = LOWER(?)', [ $tokenHash ] )
            ->first();
    }

    /**
     * Deleta tokens por user ID dentro do tenant atual.
     *
     * @param mixed $userId
     * @return bool
     */
    public function deleteByUserId( mixed $userId ): bool
    {
        // Para operações de tokens, usamos o tenant_id do contexto de teste
        $tenantId = config( 'tenant.testing_id' ) ?? 1;

        return $this->model->withoutGlobalScope( \App\Models\Traits\TenantScope::class)
            ->where( 'tenant_id', $tenantId )
            ->where( 'user_id', $userId )
            ->delete() > 0;
    }

    /**
     * Encontra tokens expirados dentro do tenant atual.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, UserConfirmationToken>
     */
    public function findExpired()
    {
        // Para operações de tokens, usamos o tenant_id do contexto de teste
        $tenantId = config( 'tenant.testing_id' ) ?? 1;

        return $this->model->withoutGlobalScope( \App\Models\Traits\TenantScope::class)
            ->where( 'tenant_id', $tenantId )
            ->where( 'expires_at', '<', now() )
            ->get();
    }

    /**
     * Remove tokens expirados dentro do tenant atual.
     *
     * @return int Número de tokens removidos
     */
    public function deleteExpired(): int
    {
        // Para operações de tokens, usamos o tenant_id do contexto de teste
        $tenantId = config( 'tenant.testing_id' ) ?? 1;

        return $this->model->withoutGlobalScope( \App\Models\Traits\TenantScope::class)
            ->where( 'tenant_id', $tenantId )
            ->where( 'expires_at', '<', now() )
            ->delete();
    }

    /**
     * Remove tokens por user ID e tipo dentro do tenant atual.
     *
     * @param mixed $userId
     * @param string $type
     * @return bool
     */
    public function deleteByUserAndType( mixed $userId, string $type ): bool
    {
        // Para operações de tokens, usamos o tenant_id do contexto de teste
        $tenantId = config( 'tenant.testing_id' ) ?? 1;

        return $this->model->withoutGlobalScope( \App\Models\Traits\TenantScope::class)
            ->where( 'tenant_id', $tenantId )
            ->where( 'user_id', $userId )
            ->where( 'type', $type )
            ->delete() > 0;
    }

    /**
     * Busca token por ID dentro do tenant atual.
     *
     * @param int $id
     * @return UserConfirmationToken|null
     */
    public function findByIdAndTenantId( int $id, int $tenantId ): ?UserConfirmationToken
    {
        return $this->model->withoutGlobalScope( \App\Models\Traits\TenantScope::class)
            ->where( 'id', $id )
            ->where( 'tenant_id', $tenantId )
            ->first();
    }

    /**
     * Busca tokens por user ID dentro do tenant atual.
     *
     * @param int $userId
     * @param array $criteria
     * @return \Illuminate\Database\Eloquent\Collection<int, UserConfirmationToken>
     */
    public function findByUserId( int $userId, array $criteria = [] ): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->model->where( 'user_id', $userId );
        $this->applyFilters( $query, $criteria );
        return $query->get();
    }

}
