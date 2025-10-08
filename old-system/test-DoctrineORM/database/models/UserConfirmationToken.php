<?php

namespace app\database\models;

use app\database\entitiesORM\UserConfirmationTokenEntity;
use app\database\Model;
use core\dbal\Entity;
use core\dbal\EntityNotFound;
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

    protected static function createEntity( array $data ): Entity
    {
        return UserConfirmationTokenEntity::create( $data );
    }

    /**
     * Busca as informações de um token de confirmação.
     *
     * @param string $token O token a ser buscado.
     * @return UserConfirmationTokenEntity|EntityNotFound A entidade do token ou nulo se não for encontrada.
     */
    public function getTokenInfo( string $token ): UserConfirmationTokenEntity|EntityNotFound
    {
        try {
            $result = $this->findOneBy( [ 'token' => $token ] );

            /** @var UserConfirmationTokenEntity|EntityNotFound $result */
            return $result;
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o token, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

}