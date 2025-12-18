<?php

namespace App\Services\Core;

use App\Models\User;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PermissionService - Gestão Granular de Permissões
 *
 * Implementa autorização específica para diferentes entidades
 * com foco em categorias (globais, personalizadas, administração)
 */
class PermissionService
{
    protected const CACHE_KEY_PREFIX = 'user_permissions:';
    protected const CACHE_TTL        = 3600; // 1 hora

    /**
     * Verifica se usuário tem permissão específica
     */
    public function hasPermission( User $user, string $permission ): bool
    {
        // Admin global sempre tem todas as permissões
        if ( $this->isAdminGlobal( $user ) ) {
            return true;
        }

        // Cache de permissões do usuário
        $permissions = $this->getUserPermissions( $user );

        return in_array( $permission, $permissions, true );
    }

    /**
     * Verifica se usuário tem qualquer uma das permissões
     */
    public function hasAnyPermission( User $user, array $permissions ): bool
    {
        foreach ( $permissions as $permission ) {
            if ( $this->hasPermission( $user, $permission ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica se usuário tem TODAS as permissões
     */
    public function hasAllPermissions( User $user, array $permissions ): bool
    {
        foreach ( $permissions as $permission ) {
            if ( !$this->hasPermission( $user, $permission ) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * ✅ PERMISSÕES DE CATEGORIAS
     */

    /**
     * Permissão para gerenciar categorias do tenant
     */
    public function canManageCategories( User $user ): bool
    {
        // Provider pode sempre gerenciar suas próprias categorias
        if ( $user->isProvider() ) {
            return true;
        }

        return $this->hasPermission( $user, 'manage-categories' ) || $this->isAdminGlobal( $user );
    }

    /**
     * Permissão para criar categorias
     */
    public function canCreateCategories( User $user ): bool
    {
        if ( !$user->tenant_id ) {
            return false;
        }

        return $this->canManageCategories( $user );
    }

    /**
     * Permissão para editar categorias
     */
    public function canEditCategories( User $user ): bool
    {
        return $this->canManageCategories( $user );
    }

    /**
     * Permissão para excluir categorias
     */
    public function canDeleteCategories( User $user ): bool
    {
        return $this->canManageCategories( $user );
    }

    /**
     * Permissão para associar categorias a produtos/serviços
     */
    public function canAssignCategories( User $user ): bool
    {
        // Providers podem sempre associar (gerencia produtos/serviços próprios)
        if ( $user->isProvider() ) {
            return true;
        }

        return $this->hasPermission( $user, 'assign-categories' ) || $this->isAdminGlobal( $user );
    }

    /**
     * ✅ PERMISSÕES PARA OUTRAS ENTIDADES
     */

    /**
     * Permissões para produtos
     */
    public function canManageProducts( User $user ): bool
    {
        return $user->isProvider() || $this->isAdminGlobal( $user );
    }

    /**
     * Permissões para serviços
     */
    public function canManageServices( User $user ): bool
    {
        return $user->isProvider() || $this->isAdminGlobal( $user );
    }

    /**
     * Permissões para clientes
     */
    public function canManageCustomers( User $user ): bool
    {
        return $user->isProvider() || $this->isAdminGlobal( $user );
    }

    /**
     * Permissões para orçamentos
     */
    public function canManageBudgets( User $user ): bool
    {
        return $user->role === 'provider' || $this->isAdminGlobal( $user );
    }

    /**
     * ✅ VERIFICAÇÕES DE CONTEXTO
     */

    /**
     * Verifica se usuário pode acessar dados de determinado tenant
     */
    public function canAccessTenantData( User $user, ?int $tenantId ): bool
    {
        // Admin global pode acessar qualquer tenant
        if ( $this->isAdminGlobal( $user ) ) {
            return true;
        }

        // Usuário só pode acessar dados do próprio tenant
        return $user->tenant_id === $tenantId;
    }

    /**
     * Verifica se pode gerenciar categoria específica do tenant
     */
    public function canManageCategory( User $user, ?int $categoryTenantId ): bool
    {
        // Provider pode gerenciar apenas do próprio tenant
        return $this->canAccessTenantData( $user, $categoryTenantId );
    }

    /**
     * ✅ MÉTODOS AUXILIARES
     */

    /**
     * Verifica se usuário é admin global
     */
    protected function isAdminGlobal( User $user ): bool
    {
        return $user->isAdmin();
    }

    /**
     * Busca permissões do usuário (com cache)
     */
    protected function getUserPermissions( User $user ): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $user->id;

        return Cache::remember( $cacheKey, self::CACHE_TTL, function () use ($user) {
            $permissions = [];

            // Carrega permissões baseadas na role do usuário
            if ( $user->isAdmin() ) {
                $permissions = $this->getAdminPermissions();
            } elseif ( $user->isProvider() ) {
                $permissions = $this->getProviderPermissions();
            } else {
                $permissions = $this->getBasicUserPermissions();
            }

            Log::info( 'User permissions loaded', [
                'user_id'           => $user->id,
                'permissions_count' => count( $permissions )
            ] );

            return $permissions;
        } );
    }

    /**
     * Permissões do admin global
     */
    protected function getAdminPermissions(): array
    {
        return [
            'view-global-categories',
            'manage-global-categories',
            'manage-custom-categories',
            'create-custom-categories',
            'edit-custom-categories',
            'delete-custom-categories',
            'use-global-as-custom',
            'assign-categories',
            'manage-products',
            'manage-services',
            'manage-customers',
            'manage-budgets',
            'view-reports',
            'system-settings',
            'user-management',
        ];
    }

    /**
     * Permissões do provider
     */
    protected function getProviderPermissions(): array
    {
        return [
            'view-global-categories',
            'manage-custom-categories',
            'create-custom-categories',
            'edit-custom-categories',
            'delete-custom-categories',
            'use-global-as-custom',
            'assign-categories',
            'manage-products',
            'manage-services',
            'manage-customers',
            'manage-budgets',
            'view-reports',
        ];
    }

    /**
     * Permissões do usuário básico
     */
    protected function getBasicUserPermissions(): array
    {
        return [
            'view-global-categories',
            'assign-categories',
        ];
    }

    /**
     * Limpa cache de permissões do usuário
     */
    public function clearUserPermissionCache( User $user ): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $user->id;
        Cache::forget( $cacheKey );

        Log::info( 'User permission cache cleared', [
            'user_id' => $user->id,
            'role'    => $user->role
        ] );
    }

    /**
     * ✅ MÉTODOS DE VALIDAÇÃO PARA CATEGORIAS
     */

    /**
     * Valida se usuário pode trabalhar com categoria específica
     */
    public function validateCategoryAccess( User $user, int $categoryId, string $action ): ServiceResult
    {
        try {
            // Busca categoria com informação do tenant
            $category = \App\Models\Category::find( $categoryId );

            if ( !$category ) {
                return ServiceResult::error( 'Categoria não encontrada' );
            }

            // Verifica acesso baseado no tipo de categoria
            switch ( $action ) {
                case 'view':
                    if ( $this->canAccessTenantData( $user, $category->tenant_id ) ) {
                        return ServiceResult::success();
                    }
                    break;

                case 'edit':
                    if ( $this->canManageCategory( $user, $category->tenant_id ) ) {
                        return ServiceResult::success();
                    }
                    break;

                case 'delete':
                    if ( $this->canManageCategory( $user, $category->tenant_id ) ) {
                        return ServiceResult::success();
                    }
                    break;
            }

            return ServiceResult::error( 'Ação não autorizada para esta categoria' );

        } catch ( \Exception $e ) {
            Log::error( 'Error validating category access', [
                'user_id'     => $user->id,
                'category_id' => $categoryId,
                'action'      => $action,
                'error'       => $e->getMessage()
            ] );

            return ServiceResult::error( 'Erro interno na validação' );
        }
    }

    /**
     * Busca categorias que usuário pode gerenciar
     */
    public function getManageableCategories( User $user ): Collection
    {
        // Admin global pode gerenciar todas
        if ( $this->isAdminGlobal( $user ) ) {
            return \App\Models\Category::orderBy( 'name' )->get();
        }

        // Provider pode gerenciar apenas categorias do próprio tenant
        if ( $user->role === 'provider' ) {
            return \App\Models\Category::where( 'tenant_id', $user->tenant_id )
                ->orderBy( 'name' )
                ->get();
        }

        // Usuário básico não pode gerenciar categorias
        return new Collection();
    }

}
