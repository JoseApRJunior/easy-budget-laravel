<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Provider;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

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
        'address',
        'address_number',
        'neighborhood',
        'city',
        'state',
        'cep',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'      => 'integer',
        'address'        => 'string',
        'address_number' => 'string',
        'neighborhood'   => 'string',
        'city'           => 'string',
        'state'          => 'string',
        'cep'            => 'string',
        'created_at'     => 'immutable_datetime',
        'updated_at'     => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Address.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'      => 'required|integer|exists:tenants,id',
            'address'        => 'required|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'neighborhood'   => 'required|string|max:100',
            'city'           => 'required|string|max:100',
            'state'          => 'required|string|max:2',
            'cep'            => 'required|string|max:9|regex:/^\d{5}-?\d{3}$/',
        ];
    }

    /**
     * Get the tenant that owns the Address.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the customer associated with the Address.
     */
    public function customer(): HasOne
    {
        return $this->hasOne( Customer::class);
    }

    /**
     * Get the providers associated with the Address.
     */
    public function providers(): HasMany
    {
        return $this->hasMany( Provider::class);
    }

}
