<?php

namespace App\Models;

use App\Models\Budget;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserConfirmationToken extends Model
{
    use TenantScoped;

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
    protected $table = 'user_confirmation_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'tenant_id',
        'token',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id'    => 'integer',
        'tenant_id'  => 'integer',
        'token'      => 'string',
        'expires_at' => 'datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo UserConfirmationToken.
     */
    public static function businessRules(): array
    {
        return [
            'user_id'    => 'required|integer|exists:users,id',
            'tenant_id'  => 'required|integer|exists:tenants,id',
            'token'      => 'required|string|size:64|unique:user_confirmation_tokens,token',
            'expires_at' => 'required|date|after:now',
        ];
    }

    /**
     * Validação customizada para verificar se o token é único no tenant.
     */
    public static function validateUniqueTokenInTenant( string $token, int $tenantId, ?int $excludeTokenId = null ): bool
    {
        $query = static::where( 'token', $token )->where( 'tenant_id', $tenantId );

        if ( $excludeTokenId ) {
            $query->where( 'id', '!=', $excludeTokenId );
        }

        return !$query->exists();
    }

    /**
     * Get the user that owns the UserConfirmationToken.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Get the tenant that owns the UserConfirmationToken.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the budgets for the UserConfirmationToken.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany( Budget::class);
    }

    /**
     * Get the schedules for the UserConfirmationToken.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany( Schedule::class);
    }

}
