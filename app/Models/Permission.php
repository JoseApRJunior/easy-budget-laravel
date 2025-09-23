<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Custom Permission model: global, aplicável a todos os tenants sem scoping por tenant_id.
 * Implementação custom RBAC sem dependências Spatie, usando relationships Eloquent.
 * Permissions são globais, mas assignments a users/roles são scoped via pivots com tenant_id.
 * Não utiliza global scopes ou filtros por tenant_id em queries base.
 */
class Permission extends Model
{
    use HasFactory;

    /**
     * Tabela associada ao modelo Permission.
     */
    protected $table = 'permissions';

    /**
     * Campos preenchíveis para o model Permission, focando em atributos globais sem tenant scoping.
     * 'name': Nome da permissão (ex: 'editar-orcamento').
     * 'slug': Slug único da permissão (ex: 'edit-budget').
     * 'description': Descrição detalhada da permissão.
     * 'group': Grupo ao qual a permissão pertence (ex: 'budget', 'user', 'admin').
     * 'guard_name': Nome do guard de autenticação (ex: 'web', 'api').
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'group',
        'guard_name',
    ];

    protected $casts = [
        'name' => 'string',
        'slug' => 'string',
        'description' => 'string',
        'group' => 'string',
        'guard_name' => 'string',
    ];

    /**
     * Relationship com roles - global, sem tenant scoping.
     * Relação many-to-many usando tabela role_permissions com tenant_id para scoping em assignments.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany( Role::class, 'role_permissions' );
    }

    /**
     * Relationship com users - tenant-scoped via roles.
     * Since user_permissions table doesn't exist, users are accessed via roles.
     * Assignments scoped por tenant through role assignments.
     *
     * @return BelongsToMany
     */
    public function users()
    {
        return User::whereHas( 'roles', function ($query) {
            $query->whereHas( 'permissions', function ($query) {
                $query->where( 'permission_id', $this->id );
            } );
        } );
    }

}

/**
 * Custom RBAC: Permissions globais com assignments scoped por tenant via pivots.
 * Sem dependências Spatie para simplicidade e compatibilidade multi-tenant.
 */
