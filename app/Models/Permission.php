<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Campos preenchíveis para o model Permission, focando em atributos globais sem tenant scoping.
     * 'name': Nome da permissão (ex: 'editar-orcamento').
     * 'guard_name': Nome do guard de autenticação (ex: 'web', 'api').
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];


    protected $casts = [
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
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Relationship com users - tenant-scoped via pivot custom.
     * Assume tabela user_permissions com tenant_id para direct permissions.
     * Se não existir, use via roles. Assignments scoped por tenant.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions', 'permission_id', 'user_id')->withPivot('tenant_id')->withTimestamps();
    }

}

/**
 * Custom RBAC: Permissions globais com assignments scoped por tenant via pivots.
 * Sem dependências Spatie para simplicidade e compatibilidade multi-tenant.
 */
