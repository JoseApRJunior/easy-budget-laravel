<?php

namespace core\library;

use app\database\models\Provider;
use app\database\models\User;
use core\dbal\EntityNotFound;

class Auth
{
    public function __construct(
        private User $user,
        private Provider $provider,
    ) {
    }

    public function attempt(string $email, string $password): array
    {
        // Buscar prestador pelo e-mail
        $provider = $this->provider->getProviderFullByEmail($email);

        // Verificar se o prestador existe
        if ($provider instanceof EntityNotFound) {
            return [
                'status' => 'error',
                'message' => 'Email ou Senhha incorreto.',
                'data' => [
                    'provider' => $provider,
                ],
            ];
        }

        // Verificar se a senha confere
        if (!isset($provider->password) || !password_verify($password, $provider->password)) {
            return [
                'status' => 'error',
                'message' => 'Email ou Senhha incorreto.',
                'data' => [
                    'provider' => $provider,
                ],
            ];
        }

        // Remover a senha do objeto Provider antes de armazenar na sessão
        $provider->password = null;

        $isActive = filter_var($provider->is_active, FILTER_VALIDATE_BOOLEAN);
        if ($isActive) {

            // Carregar dados do usuário na sessão
            Session::set('auth', $provider);

            // Carregar roles do usuário
            $roles = $this->user->getUserRoles($provider->user_id, $provider->tenant_id);
            if (empty($roles)) {
                return [
                    'status' => 'error',
                    'message' => 'Nenhuma função encontrada para o usuário.',
                    'data' => [
                        'provider' => $provider,
                    ],
                ];
            }
            Session::set('user_roles', $roles);

            // Carregar permissions do usuário
            $permissions = $this->user->getUserPermissions($provider->user_id, $provider->tenant_id);
            if (empty($permissions)) {
                return [
                    'status' => 'error',
                    'message' => 'Nenhuma permissão encontrada para o usuário.',
                    'data' => [
                        'provider' => $provider,
                    ],
                ];
            }
            Session::set('user_permissions', $permissions);

            // Verificar se o usuário é admin
            if (in_array('admin', $roles)) {
                Session::set('admin', $provider);
            }

            return [
                'status' => 'success',
                'message' => 'Login realizado com sucesso.',
                'data' => [
                    'provider' => $provider,
                ],
            ];

        } else {
            return [
                'status' => 'error',
                'message' => 'Sua conta não está ativada.',
                'data' => [
                    'provider' => $provider,
                    'is_active' => $isActive,
                ],
            ];
        }

    }

    public static function isAuth(): bool
    {
        return isset($_SESSION[ 'auth' ]);
    }

    public static function isAdmin(): bool
    {
        return isset($_SESSION[ 'admin' ]);
    }

    public static function isProvider(): bool
    {
        return Session::get('user_roles') && in_array('provider', $_SESSION[ 'user_roles' ] ?? []);
    }

    public static function hasRole(string $role): bool
    {
        return in_array($role, $_SESSION[ 'user_roles' ] ?? []);
    }

    public static function hasPermission(string $permission): bool
    {
        // Implementar lógica para verificar permissões
        // Isso pode envolver uma consulta ao banco de dados ou usar dados em cache
        return in_array($permission, $_SESSION[ 'user_permissions' ] ?? []);
    }

    public static function auth(): bool|null
    {
        return $_SESSION[ 'auth' ] ?? null;
    }

    public static function logout(): void
    {
        Session::removeAll();
    }

}
