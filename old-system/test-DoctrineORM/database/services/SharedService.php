<?php

namespace app\database\services;

use app\database\entitiesORM\UserConfirmationTokenEntity;
use app\database\models\Provider;
use app\database\models\UserConfirmationToken;
use core\dbal\EntityNotFound;
use core\library\Session;
use DateTime;
use Doctrine\DBAL\Connection;

class SharedService
{
    /**
     * Summary of table
     * @var string
     */

    protected string $table = 'user_confirmation_tokens';
    // private          $authenticated;

    public function __construct(
        // private readonly Connection $connection,
        private UserConfirmationToken $userConfirmationToken,
        private Provider $provider,
    ) {
        // if ( Session::has( 'auth' ) ) {
        //     $this->authenticated = Session::get( 'auth' );
        // }

    }

    /**
     * Valida um token de confirmação de usuário.
     *
     * @param mixed $token Token de confirmação.
     * @return array<string, mixed> Resultado da validação.
     */
    public function validateUserConfirmationToken(mixed $token): array
    {
        $userConfirmationToken = $this->userConfirmationToken->getTokenInfo($token);
        if ($userConfirmationToken instanceof EntityNotFound) {
            return [
                'status' => 'error',
                'condition' => 'not_found',
                'message' => 'Token de confirmação inválido ou não encontrado.',
            ];
        }

        // Verificar se o token expirou
        /** @var UserConfirmationTokenEntity $userConfirmationToken */
        $expiresAt = $userConfirmationToken->expires_at;
        $now = new DateTime();
        if ($expiresAt < $now) {
            return [
                'status' => 'error',
                'condition' => 'expired',
                'data' => $userConfirmationToken,
                'message' => 'Token de confirmação expirado.',
            ];
        }

        return [
            'status' => 'success',
            'condition' => 'valid',
            'message' => 'Token de confirmação válido.',
            'data' => $userConfirmationToken,
        ];
    }

    public function getProviderByToken(UserConfirmationTokenEntity $userConfirmationTokenEntity): object
    {
        // Retorna o provider completo associado ao usuário
        return $this->provider->getProviderFullByUserId($userConfirmationTokenEntity->user_id, $userConfirmationTokenEntity->tenant_id);
    }

    /**
     * Gera um novo token de confirmação de usuário.
     *
     * @param int $user_id ID do usuário.
     * @param int $tenant_id ID do tenant.
     * @return array<string, mixed> Resultado da geração do token.
     */
    public function generateNewUserConfirmationToken(int $user_id, int $tenant_id): array
    {
        // Gera um token para confirmação de conta
        [ $token, $expiresDate ] = generateTokenExpirate('+7 days');
        // popula model UserConfirmationTokenEntity
        $userConfirmationTokenEntity = UserConfirmationTokenEntity::create([
            'tenant_id' => $tenant_id,
            'user_id' => $user_id,
            'token' => $token,
            'expires_at' => $expiresDate,
        ]);

        // Criar UserConfirmationTokens e retorna o id do userConfirmationToken
        return $this->userConfirmationToken->create($userConfirmationTokenEntity);
    }

    /**
     * Busca um token de confirmação por ID.
     *
     * @param int $id ID do token.
     * @param int $tenant_id ID do tenant.
     * @return mixed Token encontrado ou EntityNotFound.
     */
    public function getUserConfirmationTokenById(int $id, int $tenant_id): mixed
    {
        return $this->userConfirmationToken->findBy([
            'id' => $id,
            'tenant_id' => $tenant_id,
        ]);
    }

}
