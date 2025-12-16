<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Custom Role model: global, aplicável a todos os tenants sem scoping por tenant_id.
     * Implementação custom RBAC sem dependências Spatie, usando relationships Eloquent.
     * Roles são globais, mas assignments a users são scoped via pivot tenant_id em user_roles.
     */

    protected $casts = [
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Role.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:255',
        ];
    }

    /**
     * Regras de validação para atualização de role.
     */
    public static function updateRules( int $roleId ): array
    {
        return [
            'name'        => 'required|string|max:255|unique:roles,name,' . $roleId,
            'description' => 'nullable|string|max:255',
        ];
    }

    /**
     * Obtém as permissões associadas a este role de forma global.
     * Relação many-to-many sem restrição por tenant, permitindo permissões compartilhadas.
     * Tabela pivot: role_permissions com timestamps.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany( Permission::class, 'role_permissions' );
    }

    /**
     * Obtém os usuários associados a este role de forma global.
     * Relação many-to-many sem scoping por tenant, usando tabela model_has_roles do Spatie.
     * Permite atribuição de roles a usuários independentemente do tenant.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany( User::class, 'user_roles', 'role_id', 'user_id' )
            ->using( UserRole::class)
            ->withPivot( 'tenant_id' )
            ->withTimestamps();
    }

    /**
     * Get users for a specific tenant.
     */
    public function usersForTenant( int $tenantId ): BelongsToMany
    {
        return $this->users()->forTenant( $tenantId );
    }

    /**
     * Check if role has users in a specific tenant.
     */
    public function hasUsersInTenant( int $tenantId ): bool
    {
        return $this->users()->forTenant( $tenantId )->exists();
    }

    /**
     * Scope para obter apenas roles ativos.
     */
    public function scopeActive( $query )
    {
        return $query->where( 'is_active', true );
    }

    /**
     * Check if role has specific permission.
     */
    public function hasPermission( string $permissionName ): bool
    {
        return $this->permissions()->where( 'name', $permissionName )->exists();
    }

    /**
     * Assign permission to role.
     */
    public function givePermissionTo( string $permissionName ): void
    {
        $permission = Permission::where( 'name', $permissionName )->first();
        if ( $permission && !$this->hasPermission( $permissionName ) ) {
            $this->permissions()->attach( $permission->id );
        }
    }

    /**
     * Revoke permission from role.
     */
    public function revokePermissionTo( string $permissionName ): void
    {
        $permission = Permission::where( 'name', $permissionName )->first();
        if ( $permission ) {
            $this->permissions()->detach( $permission->id );
        }
    }

}

/**
 * Custom RBAC: Roles globais com assignments scoped por tenant via pivot user_roles.tenant_id.
 * Sem dependências Spatie para compatibilidade multi-tenant.
 */
