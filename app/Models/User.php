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

    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'google_data',
        'is_active',
        'logo',
        'remember_token',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected $casts = [
        'tenant_id'         => 'integer',
        'name'              => 'string',
        'email'             => 'string',
        'password'          => 'hashed',
        'google_id'         => 'string',
        'avatar'            => 'string',
        'google_data'       => 'array',
        'logo'              => 'string',
        'is_active'         => 'boolean',
        'remember_token'    => 'string',
        'email_verified_at' => 'datetime',
        'created_at'        => 'immutable_datetime',
        'updated_at'        => 'datetime',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name'      => 'nullable|string|max:150',
            'email'     => 'required|email|max:100|unique:users,email',
            'password'  => 'nullable|string|min:8|max:255|confirmed',
            'google_id' => 'nullable|string|max:255',
            'avatar'    => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'logo'      => 'nullable|string|max:255',
        ];
    }

    public static function validateUniqueEmailInTenant( string $email, int $tenantId, ?int $excludeUserId = null ): bool
    {
        $query = static::where( 'email', $email )->where( 'tenant_id', $tenantId );

        if ( $excludeUserId ) {
            $query->where( 'id', '!=', $excludeUserId );
        }

        return !$query->exists();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    public function provider(): HasOne
    {
        return $this->hasOne( Provider::class);
    }

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

    public function getTenantScopedRoles()
    {
        return $this->roles()->wherePivot( 'tenant_id', $this->tenant_id );
    }

    public function permissions()
    {
        return Permission::whereHas( 'roles', function ( $query ) {
            $query->whereHas( 'users', function ( $query ) {
                $query->where( 'user_id', $this->id )
                    ->where( 'tenant_id', $this->tenant_id );
            } );
        } );
    }

    public function attachRole( $role ): void
    {
        $roleId = $role instanceof Role ? $role->getKey() : $role;
        $this->getTenantScopedRoles()->attach( $roleId, [ 'tenant_id' => $this->tenant_id ] );
    }

    public function detachRole( $role ): void
    {
        $roleId = $role instanceof Role ? $role->getKey() : $role;
        $this->getTenantScopedRoles()->detach( $roleId );
    }

    public function activities(): HasMany
    {
        return $this->hasMany( Activity::class);
    }

    public function userConfirmationTokens(): HasMany
    {
        return $this->hasMany( UserConfirmationToken::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne( UserSettings::class);
    }

    public function scopeActive( $query ): Builder
    {
        return $query->where( 'is_active', true );
    }

    public function hasRole( string $role ): bool
    {
        return $this->getTenantScopedRoles()->where( 'name', $role )->exists();
    }

    public function hasRoleInTenant( string $role, int $tenantId ): bool
    {
        return $this->roles()
            ->wherePivot( 'tenant_id', $tenantId )
            ->where( 'name', $role )
            ->exists();
    }

    public function hasRoles( array $roles ): bool
    {
        return $this->getTenantScopedRoles()->whereIn( 'name', $roles )->count() === count( $roles );
    }

    public function hasAnyRole( array $roles ): bool
    {
        return $this->getTenantScopedRoles()->whereIn( 'name', $roles )->exists();
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    public function getNameAttribute(): string
    {
        // Prioriza o campo name se estiver preenchido (para Google OAuth)
        if ( !empty( $this->attributes[ 'name' ] ) ) {
            return $this->attributes[ 'name' ];
        }

        // Fallback para dados do provider se disponível
        if ( $this->provider?->commonData ) {
            return $this->provider->commonData->first_name . ' ' . $this->provider->commonData->last_name;
        }

        // Último fallback para e-mail
        return $this->attributes[ 'email' ] ?? '';
    }

    /* ==========================
     * Métodos de Plano/Assinatura
     * ========================== */

    public function activePlan(): ?object
    {
        $provider = $this->provider;
        if ( !$provider ) {
            return null;
        }

        $activeSubscription = $provider->planSubscriptions()
            ->where( 'status', PlanSubscription::STATUS_ACTIVE )
            ->where( 'end_date', '>', now() )
            ->with( 'plan' )
            ->first();

        return $activeSubscription?->plan;
    }

    public function pendingPlan(): ?object
    {
        $provider = $this->provider;
        if ( !$provider ) {
            return null;
        }

        $pendingSubscription = $provider->planSubscriptions()
            ->where( 'status', PlanSubscription::STATUS_PENDING )
            ->with( 'plan' )
            ->first();

        if ( !$pendingSubscription || !$pendingSubscription->plan ) {
            return null;
        }

        $result                     = $pendingSubscription->plan;
        $result->status             = $pendingSubscription->status;
        $result->subscription_id    = $pendingSubscription->id;
        $result->transaction_amount = $pendingSubscription->transaction_amount;

        return $result;
    }

    public function isTrial(): bool
    {
        $provider = $this->provider;
        if ( !$provider ) {
            return true; // Sem provider = trial
        }

        $activeSubscription = $provider->planSubscriptions()
            ->where( 'status', PlanSubscription::STATUS_ACTIVE )
            ->where( 'end_date', '>', now() )
            ->first();

        if ( !$activeSubscription ) {
            return true; // Sem assinatura ativa = trial
        }

        return strtolower( $activeSubscription->payment_method ) === 'trial'
            && $activeSubscription->transaction_amount <= 0;
    }

    public function isTrialExpired(): bool
    {
        $provider = $this->provider;
        if ( !$provider ) {
            return false;
        }

        $activeSubscription = $provider->planSubscriptions()
            ->where( 'status', PlanSubscription::STATUS_ACTIVE )
            ->first();

        if ( !$activeSubscription ) {
            return false;
        }

        return $activeSubscription->end_date < now();
    }

}
