<?php

namespace app\database\services;

use app\database\entities\UserConfirmationTokenEntity;
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

    public function validateUserConfirmationToken($token)
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

    public function getUserConfirmationTokenById(int $id, int $tenant_id)
    {
        return $this->userConfirmationToken->findBy([
            'id' => $id,
            'tenant_id' => $tenant_id,
        ]);
    }

}
