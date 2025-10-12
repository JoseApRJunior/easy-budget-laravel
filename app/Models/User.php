<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Activity;
use App\Models\Permission;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\UserConfirmationToken;
use App\Models\UserRole;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, TenantScoped, Notifiable;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'email',
        'password',
        'is_active',
        'logo',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'         => 'integer',
        'email'             => 'string',
        'password'          => 'hashed',
        'logo'              => 'string',
        'is_active'         => 'boolean',
        'remember_token'    => 'string',
        'email_verified_at' => 'datetime',
        'created_at'        => 'immutable_datetime',
        'updated_at'        => 'datetime',
    ];

    /**
     * Campos que devem ser tratados como datas imutáveis.
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Regras de validação para o modelo User.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'email'     => 'required|email|max:100|unique:users,email',
            'password'  => 'required|string|min:8|max:255|confirmed',
            'is_active' => 'boolean',
            'logo'      => 'nullable|string|max:255',
        ];
    }

    /**
     * Regras de validação para criação de usuário.
     */
    public static function createRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'email'     => 'required|email|max:100|unique:users,email',
            'password'  => 'required|string|min:8|max:255|confirmed',
            'is_active' => 'boolean',
            'logo'      => 'nullable|string|max:255',
        ];
    }

    /**
     * Regras de validação para atualização de usuário.
     */
    public static function updateRules( int $userId ): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'email'     => 'required|email|max:100|unique:users,email,' . $userId,
            'password'  => 'nullable|string|min:8|max:255|confirmed',
            'is_active' => 'boolean',
            'logo'      => 'nullable|string|max:255',
        ];
    }

    /**
     * Validação customizada para verificar se o email é único no tenant.
     */
    public static function validateUniqueEmailInTenant( string $email, int $tenantId, ?int $excludeUserId = null ): bool
    {
        $query = static::where( 'email', $email )->where( 'tenant_id', $tenantId );

        if ( $excludeUserId ) {
            $query->where( 'id', '!=', $excludeUserId );
        }

        return !$query->exists();
    }

    /**
     * Get the tenant that owns the User.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the provider associated with the User.
     */
    public function provider(): HasOne
    {
        return $this->hasOne( Provider::class);
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $notification = new VerifyEmailNotification();
        $notification->handle( $this );
    }

    /**
     * The roles that belong to the user.
     */
    /**
     * Relationship com roles - tenant-scoped via pivot.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',
            'user_id',
            'role_id',
        )->using( UserRole::class)
            ->withPivot( [ 'tenant_id' ] )
            ->withTimestamps();
    }

    /**
     * Get roles with tenant scoping applied.
     * Use this method when you need to ensure tenant isolation for roles.
     */
    public function getTenantScopedRoles()
    {
        return $this->roles()->wherePivot( 'tenant_id', $this->tenant_id );
    }

    /**
     * The permissions that belong to the user through roles.
     * Since user_permissions table doesn't exist, permissions are accessed via roles.
     */
    public function permissions()
    {
        return Permission::whereHas( 'roles', function ( $query ) {
            $query->whereHas( 'users', function ( $query ) {
                $query->where( 'user_id', $this->id )
                    ->where( 'tenant_id', $this->tenant_id );
            } );
        } );
    }

    /**
     * Attach a role to the user with current tenant ID.
     *
     * @param int|Role $role
     * @return void
     */
    public function attachRole( $role ): void
    {
        $roleId = $role instanceof Role ? $role->getKey() : $role;
        $this->getTenantScopedRoles()->attach( $roleId, [ 'tenant_id' => $this->tenant_id ] );
    }

    /**
     * Detach a role from the user with current tenant ID.
     *
     * @param int|Role $role
     * @return void
     */
    public function detachRole( $role ): void
    {
        $roleId = $role instanceof Role ? $role->getKey() : $role;
        $this->getTenantScopedRoles()->detach( $roleId );
    }

    /**
     * Atividades realizadas por este usuário.
     */
    public function activities(): HasMany
    {
        return $this->hasMany( Activity::class);
    }

    /**
     * Tokens de confirmação deste usuário.
     */
    public function userConfirmationTokens(): HasMany
    {
        return $this->hasMany( UserConfirmationToken::class);
    }

    /**
     * Scope para usuários ativos.
     */
    public function scopeActive( $query ): Builder
    {
        return $query->where( 'is_active', true );
    }

    /**
     * Verifica se o usuário tem uma role específica no tenant atual.
     */
    public function hasRole( string $role ): bool
    {
        return $this->getTenantScopedRoles()->where( 'name', $role )->exists();
    }

    /**
     * Verifica se o usuário tem uma role específica em um tenant específico.
     */
    public function hasRoleInTenant( string $role, int $tenantId ): bool
    {
        return $this->roles()
            ->wherePivot( 'tenant_id', $tenantId )
            ->where( 'name', $role )
            ->exists();
    }

    /**
     * Verifica se o usuário tem múltiplas roles no tenant atual.
     */
    public function hasRoles( array $roles ): bool
    {
        return $this->getTenantScopedRoles()->whereIn( 'name', $roles )->count() === count( $roles );
    }

    /**
     * Verifica se o usuário tem pelo menos uma das roles especificadas no tenant atual.
     */
    public function hasAnyRole( array $roles ): bool
    {
        return $this->getTenantScopedRoles()->whereIn( 'name', $roles )->exists();
    }

    /**
     * Get the email address that should be used for verification.
     */
    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    /**
     * Accessor para obter o nome do usuário.
     * Retorna o nome completo do provider se disponível, caso contrário retorna o email.
     */
    public function getNameAttribute(): string
    {
        return $this->provider?->commonData
            ? ( $this->provider->commonData->first_name . ' ' . $this->provider->commonData->last_name )
            : ( $this->attributes[ 'email' ] ?? '' );
    }

}
