<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
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
    protected $table = 'addresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'provider_id',
        'address',
        'address_number',
        'neighborhood',
        'city',
        'state',
        'cep',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'address' => null,
        'address_number' => null,
        'neighborhood' => null,
        'city' => null,
        'state' => null,
        'cep' => null,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'customer_id' => 'integer',
        'provider_id' => 'integer',
        'address' => 'string',
        'address_number' => 'string',
        'neighborhood' => 'string',
        'city' => 'string',
        'state' => 'string',
        'cep' => 'string',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Address.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'address' => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'neighborhood' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'provider_id' => 'nullable|integer|exists:providers,id',
            'cep' => 'nullable|string|max:9|regex:/^\d{5}-?\d{3}$/',
        ];
    }

    /**
     * Get the tenant that owns the Address.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the customer associated with the Address.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the provider associated with the Address.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
