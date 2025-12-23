<?php

namespace App\Models;

use App\Enums\TokenType;
use App\Models\Budget;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class UserConfirmationToken extends Model
{
    use HasFactory, TenantScoped;

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
        'type',
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
        'type'       => 'string',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Set the type attribute.
     * Convert enum to string value when saving to database.
     */
    public function setTypeAttribute( $value ): void
    {
        if ( $value instanceof TokenType ) {
            $this->attributes[ 'type' ] = $value->value;
        } else {
            $this->attributes[ 'type' ] = $value;
        }
    }

    /**
     * Get the type attribute.
     * Convert string value back to enum when loading from database.
     */
    public function getTypeAttribute( $value ): TokenType
    {
        $tokenType = TokenType::tryFrom( $value );

        if ( $tokenType === null ) {
            // Log warning for invalid token type and return default
            Log::warning( 'Invalid token type found in database', [
                'token_type_value' => $value,
                'token_id'         => $this->id,
                'user_id'          => $this->user_id,
                'tenant_id'        => $this->tenant_id,
            ] );

            // Return default EMAIL_VERIFICATION for backward compatibility
            return TokenType::EMAIL_VERIFICATION;
        }

        return $tokenType;
    }

    /**
     * Regras de validação para o modelo UserConfirmationToken.
     */
    public static function businessRules(): array
    {
        return [
            'user_id'    => 'required|integer|exists:users,id',
            'tenant_id'  => 'required|integer|exists:tenants,id',
            'token'      => 'required|string|size:43|unique:user_confirmation_tokens,token', // base64url format: 32 bytes = 43 caracteres
            'expires_at' => 'required|date|after:now',
            'type'       => 'required|string|in:' . implode( ',', TokenType::getAllTypes() ),
        ];
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
