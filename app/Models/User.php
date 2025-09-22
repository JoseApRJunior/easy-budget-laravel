<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Activity;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable
{
    use HasFactory;
    use TenantScoped;

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
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'is_active',
        'logo',
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
        'name'              => 'string',
        'first_name'        => 'string',
        'last_name'         => 'string',
        'email'             => 'string',
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'phone'             => 'string',
        'address'           => 'string',
        'city'              => 'string',
        'state'             => 'string',
        'zip_code'          => 'string',
        'logo'              => 'string',
        'is_active'         => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

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
        )->withPivot( [ 'tenant_id' ] )
            ->withTimestamps();
    }

    /**
     * The permissions that belong to the user.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'user_permissions',
            'user_id',
            'permission_id',
        )->withPivot( 'tenant_id' )
            ->withTimestamps();
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
        $this->roles()->attach( $roleId, [ 'tenant_id' => $this->tenant_id ] );
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
        $this->roles()->wherePivot( 'tenant_id', $this->tenant_id )->detach( $roleId );
    }

    /**
     * Atividades realizadas por este usuário.
     */
    public function activities(): HasMany
    {
        return $this->hasMany( Activity::class);
    }

    /**
     * Boot method para validação de unicidade de email por tenant.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating( function ($user) {
            // Validação de unicidade de email por tenant
            $existing = self::where( 'tenant_id', $user->tenant_id )
                ->where( 'email', $user->email )
                ->first();

            if ( $existing ) {
                throw new \Exception( 'Email já existe para este tenant.' );
            }
        } );
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
        return $this->roles->contains( 'name', $role );
    }

}
