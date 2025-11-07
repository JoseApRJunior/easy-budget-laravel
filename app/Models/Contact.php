<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Contact extends Model
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
    protected $table = 'contacts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'provider_id',
        'email_personal',
        'phone_personal',
        'email_business',
        'phone_business',
        'website',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'email_personal' => null,
        'phone_personal' => null,
        'email_business' => null,
        'phone_business' => null,
        'website'        => null,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'      => 'integer',
        'customer_id'    => 'integer',
        'provider_id'    => 'integer',
        'email_personal' => 'string',
        'phone_personal' => 'string',
        'email_business' => 'string',
        'phone_business' => 'string',
        'website'        => 'string',
        'created_at'     => 'immutable_datetime',
        'updated_at'     => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Contact.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'      => 'required|integer|exists:tenants,id',
            'customer_id'    => 'nullable|integer|exists:customers,id',
            'provider_id'    => 'nullable|integer|exists:providers,id',
            'email_personal' => 'nullable|email|max:255',
            'phone_personal' => 'nullable|string|max:20',
            'email_business' => 'nullable|email|max:255',
            'phone_business' => 'nullable|string|max:20',
            'website'        => 'nullable|url|max:255',
        ];
    }

    /**
     * Get the tenant that owns the Contact.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the customer associated with the Contact.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo( Customer::class);
    }

    /**
     * Get the provider associated with the Contact.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo( Provider::class);
    }

}
