<?php

declare(strict_types=1);

namespace app\interfaces;

use app\database\entitiesORM\SessionEntity;
use app\database\entitiesORM\UserEntity;
use app\support\ServiceResult;

/**
 * Interface AuthenticationServiceInterface
 *
 * Define o contrato para serviços de autenticação no sistema.
 * Responsável por gerenciar autenticação, autorização e sessões de usuários.
 */
interface AuthenticationServiceInterface
{
    /**
     * Verifica se o usuário está autenticado
     *
     * @return bool True se estiver autenticado, false caso contrário
     */
    public function isAuthenticated(): bool;

    /**
     * Obtém o usuário autenticado atual
     *
     * @return UserEntity|null Entidade do usuário ou null se não autenticado
     */
    public function getAuthenticatedUser(): ?UserEntity;

    /**
     * Verifica se o usuário possui uma role específica
     *
     * @param string $role Nome da role a verificar
     * @return bool True se possuir a role, false caso contrário
     */
    public function hasRole( string $role ): bool;

    /**
     * Verifica se o usuário possui uma permissão específica
     *
     * @param string $permission Nome da permissão a verificar
     * @return bool True se possuir a permissão, false caso contrário
     */
    public function hasPermission( string $permission ): bool;

    /**
     * Verifica se o usuário é administrador
     *
     * @return bool True se for administrador, false caso contrário
     */
    public function isAdmin(): bool;

    /**
     * Verifica se o usuário é um provedor
     *
     * @return bool True se for provedor, false caso contrário
     */
    public function isProvider(): bool;

    /**
     * Obtém a sessão atual do usuário
     *
     * @return SessionEntity|null Entidade da sessão ou null se não encontrada
     */
    public function getCurrentSession(): ?SessionEntity;

    /**
     * Invalida a sessão atual do usuário
     *
     * @return ServiceResult Resultado da operação
     */
    public function invalidateCurrentSession(): ServiceResult;

    /**
     * Invalida todas as sessões de um usuário
     *
     * @param int $userId ID do usuário
     * @return ServiceResult Resultado da operação
     */
    public function invalidateAllUserSessions( int $userId ): ServiceResult;

    /**
     * Valida se a sessão atual é válida
     *
     * @return bool True se a sessão for válida, false caso contrário
     */
    public function validateCurrentSession(): bool;

    /**
     * Obtém as roles do usuário autenticado
     *
     * @return array<string> Array com as roles do usuário
     */
    public function getUserRoles(): array;

    /**
     * Obtém as permissões do usuário autenticado
     *
     * @return array<string> Array com as permissões do usuário
     */
    public function getUserPermissions(): array;

    /**
     * Verifica se o usuário pode acessar área administrativa
     *
     * @return bool True se pode acessar, false caso contrário
     */
    public function canAccessAdmin(): bool;

    /**
     * Verifica se o usuário pode acessar área de provedor
     *
     * @return bool True se pode acessar, false caso contrário
     */
    public function canAccessProvider(): bool;
}
