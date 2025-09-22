<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\UserConfirmationToken;
use App\Repositories\AbstractRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para tokens de confirmação de usuário.
 *
 * Estende AbstractRepository para operações tenant-aware.
 */
class UserConfirmationTokenRepository extends AbstractRepository
{
    /**
     * @var string Model class
     */
    protected string $modelClass = UserConfirmationToken::class;

    /**
     * Encontra token por token hash e tenant.
     *
     * @param string $tokenHash
     * @param int $tenantId
     * @return Model|null
     */
    public function findByTokenAndTenantId( string $tokenHash, int $tenantId ): ?Model
    {
        return $this->findOneByAndTenantId( [ 'token' => $tokenHash ], $tenantId );
    }

    /**
     * Deleta tokens por user ID.
     *
     * @param mixed $userId
     * @return bool
     */
    public function deleteByUserId( mixed $userId ): bool
    {
        return $this->model::where( 'user_id', $userId )->delete() > 0;
    }

    // Outros métodos se necessário
}
