<?php

namespace App\Models;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserRole extends Pivot
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'role_id',
        'tenant_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id'    => 'integer',
        'role_id'    => 'integer',
        'tenant_id'  => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo UserRole.
     */
    public static function businessRules(): array
    {
        return [
            'user_id'   => 'required|integer|exists:users,id',
            'role_id'   => 'required|integer|exists:roles,id',
            'tenant_id' => 'required|integer|exists:tenants,id',
        ];
    }

    /**
     * Regras de validação para criação de UserRole.
     */
    public static function createRules(): array
    {
        return [
            'user_id'   => 'required|integer|exists:users,id',
            'role_id'   => 'required|integer|exists:roles,id',
            'tenant_id' => 'required|integer|exists:tenants,id',
        ];
    }

    /**
     * Regras de validação para atualização de UserRole.
     */
    public static function updateRules(): array
    {
        return [
            'user_id'   => 'required|integer|exists:users,id',
            'role_id'   => 'required|integer|exists:roles,id',
            'tenant_id' => 'required|integer|exists:tenants,id',
        ];
    }

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the user that owns the UserRole.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Get the role that owns the UserRole.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo( Role::class);
    }

    /**
     * Get the tenant that owns the UserRole.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Verifica se esta atribuição de role está ativa para o tenant especificado.
     */
    public function isActiveForTenant(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }

    /**
     * Scope para buscar UserRoles por tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para buscar UserRoles por usuário.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para buscar UserRoles por role.
     */
    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

}
