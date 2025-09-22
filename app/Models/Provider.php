<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Budget;
use App\Models\PlanSubscription;
use App\Models\Service;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Provider extends Model
{
    use TenantScoped;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'providers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'tenant_id',
        'user_id',
        'common_data_id',
        'contact_id',
        'address_id',
        'terms_accepted',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'tenant_id'      => 'integer',
        'user_id'        => 'integer',
        'common_data_id' => 'integer',
        'contact_id'     => 'integer',
        'address_id'     => 'integer',
        'terms_accepted' => 'boolean',
        'created_at'     => 'immutable_datetime',
        'updated_at'     => 'immutable_datetime',
    ];

    /**
     * Orçamentos criados por este provedor.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany( Budget::class);
    }

    /**
     * Serviços oferecidos por este provedor.
     */
    public function services(): HasMany
    {
        return $this->hasMany( Service::class);
    }

    /**
     * Assinaturas de planos associadas a este provedor.
     */
    public function planSubscriptions(): HasMany
    {
        return $this->hasMany( PlanSubscription::class);
    }

    /**
     * Boot method para validação de unicidade (user_id, tenant_id).
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating( function ($provider) {
            $existing = $provider->newQuery()
                ->where( 'user_id', $provider->user_id )
                ->where( 'tenant_id', $provider->tenant_id )
                ->first();

            if ( $existing ) {
                throw new \Exception( 'Provedor já existe para este usuário e tenant.' );
            }
        } );
    }

    /**
     * Scope para provedores ativos.
     */
    public function scopeActive( $query )
    {
        return $query->whereHas( 'user', function ($q) {
            $q->where( 'is_active', true );
        } );
    }

    /**
     * ProviderCredentials associadas a este provedor (se aplicável).
     */
    public function providerCredentials(): HasMany
    {
        return $this->hasMany( ProviderCredential::class);
    }

    /**
     * Get the tenant that owns the Provider.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the user associated with the Provider.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Get the common data associated with the Provider.
     */
    public function commonData(): BelongsTo
    {
        return $this->belongsTo( CommonData::class);
    }

    /**
     * Get the contact associated with the Provider.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo( Contact::class);
    }

    /**
     * Get the address associated with the Provider.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo( Address::class);
    }

}
