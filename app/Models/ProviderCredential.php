<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class ProviderCredential extends Model
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
    protected $table = 'provider_credentials';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_gateway',
        'access_token_encrypted',
        'refresh_token_encrypted',
        'public_key',
        'user_id_gateway',
        'expires_in',
        'provider_id',
        'tenant_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'provider_id' => 'integer',
        'tenant_id' => 'integer',
        'expires_in' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo ProviderCredential.
     */
    public static function businessRules(): array
    {
        return [
            'provider_id' => 'required|exists:providers,id',
            'tenant_id' => 'required|exists:tenants,id',
            'payment_gateway' => 'required|string|max:50|in:mercadopago,pagseguro,stripe',
            'user_id_gateway' => 'required|string|max:50',
            'access_token_encrypted' => 'required|string',
            'refresh_token_encrypted' => 'required|string',
            'public_key' => 'required|string|max:50',
            'expires_in' => 'nullable|integer',
        ];
    }

    /**
     * Get the provider that owns the ProviderCredential.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get the tenant that owns the ProviderCredential.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
