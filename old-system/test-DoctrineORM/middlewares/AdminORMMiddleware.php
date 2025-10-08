<?php

declare(strict_types=1);

namespace core\middlewares;

use app\database\servicesORM\AuthenticationService;
use app\database\servicesORM\SessionService;
use core\library\AuthService;
use core\library\Session;
use http\Redirect;

/**
 * Middleware de administrador seguindo padrão ORM da documentação
 */
class AdminORMMiddleware extends AbstractORMMiddleware
{
    /**
     * Executa a verificação específica do middleware admin
     *
     * @return Redirect|null
     */
    protected function performCheck(): Redirect|null
    {
        error_log( "[DEBUG] AdminORMMiddleware::performCheck() - INICIANDO" );

        // Log da sessão atual
        error_log( "[DEBUG] Sessão atual: " . json_encode( [ 
            'auth'      => isset( $_SESSION[ 'auth' ] ),
            'admin'     => isset( $_SESSION[ 'admin' ] ),
            'userRoles' => $_SESSION[ 'userRoles' ] ?? 'não definido'
        ] ) );

        // Testar handleLastUpdateSession
        $needsCheck = handleLastUpdateSession( 'admin' );
        error_log( "[DEBUG] handleLastUpdateSession('admin'): " . ( $needsCheck ? 'true' : 'false' ) );

        if ( $needsCheck ) {
            error_log( "[DEBUG] Verificando Auth::isAdmin()..." );
            $isAdmin = AuthService::isAdmin();
            error_log( "[DEBUG] Auth::isAdmin(): " . ( $isAdmin ? 'true' : 'false' ) );

            if ( !$isAdmin ) {
                error_log( "[DEBUG] REDIRECIONANDO - Usuário não é admin" );
                return $this->createRedirect( '/' );
            }

            error_log( "[DEBUG] Usuário é admin - PERMITINDO ACESSO" );
        } else {
            error_log( "[DEBUG] handleLastUpdateSession=false - PERMITINDO ACESSO SEM VERIFICAÇÃO" );
        }

        error_log( "[DEBUG] AdminORMMiddleware::performCheck() - FINALIZANDO COM SUCESSO" );
        return null;
    }

    /**
     * Retorna a chave da sessão para este middleware
     *
     * @return string
     */
    protected function getSessionKey(): string
    {
        return 'admin';
    }

    public static function isAuth(): bool
    {
        return isset( $_SESSION[ 'auth' ] );
    }

    public static function isProvider(): bool
    {
        return Session::get( 'userRoles' ) && in_array( 'provider', $_SESSION[ 'userRoles' ] ?? [] );
    }

    public static function hasRole( string $role ): bool
    {
        return in_array( $role, $_SESSION[ 'userRoles' ] ?? [] );
    }

    public static function hasPermission( string $permission ): bool
    {
        // Implementar lógica para verificar permissões
        // Isso pode envolver uma consulta ao banco de dados ou usar dados em cache
        return in_array( $permission, $_SESSION[ 'userPermissions' ] ?? [] );
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