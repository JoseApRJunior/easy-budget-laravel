<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Permission;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Repositório para gerenciar permissões com operações não tenant-aware
 *
 * Estende AbstractNoTenantRepository para operações globais
 * de gerenciamento de permissões do sistema
 */
class PermissionRepository extends AbstractGlobalRepository
{
    /**
     * {@inheritdoc}
     */
    protected string $modelClass = Permission::class;

    /**
     * Busca permissão por nome
     *
     * @param string $name Nome da permissão
     * @return Permission|null Permissão encontrada ou null
     */
    public function findByName( string $name ): ?Permission
    {
        try {
            $permission = Permission::where( 'name', $name )->first();

            $this->logOperation( 'findByName', [
                'name'  => $name,
                'found' => $permission !== null
            ] );

            return $permission;
        } catch ( Throwable $e ) {
            $this->logError( 'findByName', $e, [ 'name' => $name ] );
            return null;
        }
    }

    /**
     * Lista todas as permissões ordenadas por nome
     *
     * @return array Array de permissões
     */
    public function findAllOrdered(): array
    {
        try {
            $permissions = Permission::orderBy( 'name' )->get()->toArray();

            $this->logOperation( 'findAllOrdered', [
                'permissions_count' => count( $permissions )
            ] );

            return $permissions;
        } catch ( Throwable $e ) {
            $this->logError( 'findAllOrdered', $e );
            return [];
        }
    }

    /**
     * Cria uma nova permissão
     *
     * @param array $data Dados da permissão
     * @return Permission|false Permissão criada ou false em caso de erro
     */
    public function createPermission( array $data ): Permission|false
    {
        try {
            $permission = new Permission( $data );
            $permission->save();

            $this->logOperation( 'createPermission', [
                'data'          => $data,
                'permission_id' => $permission->id,
                'success'       => true
            ] );

            return $permission;
        } catch ( Throwable $e ) {
            $this->logError( 'createPermission', $e, [ 'data' => $data ] );
            return false;
        }
    }

    /**
     * Atualiza uma permissão existente
     *
     * @param int $id ID da permissão
     * @param array $data Dados para atualização
     * @return Permission|false Permissão atualizada ou false em caso de erro
     */
    public function updatePermission( int $id, array $data ): Permission|false
    {
        try {
            $permission = Permission::find( $id );

            if ( !$permission ) {
                $this->logError( 'updatePermission', new Exception( 'Permission not found' ), [ 'id' => $id ] );
                return false;
            }

            $permission->fill( $data );
            $permission->save();

            $this->logOperation( 'updatePermission', [
                'id'      => $id,
                'success' => true
            ] );

            return $permission;
        } catch ( Throwable $e ) {
            $this->logError( 'updatePermission', $e, [ 'id' => $id, 'data' => $data ] );
            return false;
        }
    }

    /**
     * Remove uma permissão
     *
     * @param int $id ID da permissão
     * @return bool True se removido com sucesso
     */
    public function deletePermission( int $id ): bool
    {
        try {
            $deleted = Permission::destroy( $id );

            $success = $deleted > 0;

            $this->logOperation( 'deletePermission', [
                'id'      => $id,
                'success' => $success
            ] );

            return $success;
        } catch ( Throwable $e ) {
            $this->logError( 'deletePermission', $e, [ 'id' => $id ] );
            return false;
        }
    }

    /**
     * Verifica se uma permissão possui roles associadas
     *
     * @param int $permissionId ID da permissão
     * @return bool True se possui roles associadas
     */
    public function hasAssociatedRoles( int $permissionId ): bool
    {
        try {
            $hasRoles = Permission::find( $permissionId )
                ->roles()
                ->exists();

            $this->logOperation( 'hasAssociatedRoles', [
                'permission_id' => $permissionId,
                'has_roles'     => $hasRoles
            ] );

            return $hasRoles;
        } catch ( Throwable $e ) {
            $this->logError( 'hasAssociatedRoles', $e, [ 'permission_id' => $permissionId ] );
            return false;
        }
    }

}
