<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'name',
        'slug',
    ];

    /**
     * Custom Role model: global, aplicável a todos os tenants sem scoping por tenant_id.
     * Implementação custom RBAC sem dependências Spatie, usando relationships Eloquent.
     * Roles são globais, mas assignments a users são scoped via pivot tenant_id em user_roles.
     */

    protected $casts = [ 
        'guard_name' => 'string',
    ];

    /**
     * Obtém as permissões associadas a este role de forma global.
     * Relação many-to-many sem restrição por tenant, permitindo permissões compartilhadas.
     * Tabela pivot: role_permissions com timestamps.
     *
     * @return BelongsToMany
     */
    /**
     * Relationship com permissions - global, sem tenant scoping.
     * Relação many-to-many usando tabela role_permissions, aplicável a todos tenants.
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
    /**
     * Relationship com users - tenant-scoped via pivot.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany( User::class, 'user_roles', 'role_id', 'user_id' )->withPivot( 'tenant_id' )->withTimestamps();
    }

}

/**
 * Custom RBAC: Roles globais com assignments scoped por tenant via pivot user_roles.tenant_id.
 * Sem dependências Spatie para compatibilidade multi-tenant.
 */
