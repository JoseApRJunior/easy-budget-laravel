<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

/**
 * User Provider otimizado com cache em memória para evitar queries duplicadas
 */
class CachedEloquentUserProvider extends EloquentUserProvider
{
    /**
     * Cache de usuários carregados durante o request
     *
     * @var array
     */
    protected array $userCache = [];

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $cacheKey = "id:{$identifier}";

        if (isset($this->userCache[$cacheKey])) {
            return $this->userCache[$cacheKey];
        }

        $user = parent::retrieveById($identifier);

        if ($user) {
            $this->userCache[$cacheKey] = $user;
        }

        return $user;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) || (count($credentials) === 1 && isset($credentials['password']))) {
            return null;
        }

        // Criar chave de cache baseada nas credenciais
        $cacheKey = "cred:" . md5(json_encode($credentials));

        if (isset($this->userCache[$cacheKey])) {
            return $this->userCache[$cacheKey];
        }

        $user = parent::retrieveByCredentials($credentials);

        if ($user) {
            $this->userCache[$cacheKey] = $user;
            // Também cachear por ID
            $this->userCache["id:{$user->getAuthIdentifier()}"] = $user;
        }

        return $user;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        $cacheKey = "token:{$identifier}:{$token}";

        if (isset($this->userCache[$cacheKey])) {
            return $this->userCache[$cacheKey];
        }

        $user = parent::retrieveByToken($identifier, $token);

        if ($user) {
            $this->userCache[$cacheKey] = $user;
            // Também cachear por ID
            $this->userCache["id:{$user->getAuthIdentifier()}"] = $user;
        }

        return $user;
    }
}
