<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\UserConfirmationToken;
use App\Repositories\Abstracts\AbstractGlobalRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para tokens de confirmação de usuário.
 *
 * Estende AbstractGlobalRepository para operações globais
 * pois tokens de confirmação são independentes de tenant.
 */
class UserConfirmationTokenRepository extends AbstractGlobalRepository
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
        return $this->model->whereRaw( 'LOWER(token) = LOWER(?)', [ $tokenHash ] )->first();
    }

    /**
     * Deleta tokens por user ID dentro do tenant atual.
     *
     * @param mixed $userId
     * @return bool
     */
    public function deleteByUserId( mixed $userId ): bool
    {
        return $this->model->where( 'user_id', $userId )->delete() > 0;
    }

    /**
     * Encontra tokens expirados dentro do tenant atual.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, UserConfirmationToken>
     */
    public function findExpired()
    {
        return $this->model->where( 'expires_at', '<', now() )->get();
    }

    /**
     * Remove tokens expirados dentro do tenant atual.
     *
     * @return int Número de tokens removidos
     */
    public function deleteExpired(): int
    {
        return $this->model->where( 'expires_at', '<', now() )->delete();
    }

}
