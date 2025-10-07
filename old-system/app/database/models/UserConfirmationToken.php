<?php

namespace app\database\models;

use app\database\entities\UserConfirmationTokenEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class UserConfirmationToken extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'user_confirmation_tokens';

    protected static function createEntity(array $data): Entity
    {
        return UserConfirmationTokenEntity::create($data);
    }

    public function getTokenInfo(string $token): UserConfirmationTokenEntity|Entity
    {

        try {
            $entity = $this->findBy([ 'token' => $token ]);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o token, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

}
