<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use HasFactory, SoftDeletes, TenantScoped; // Já está correto no contexto fornecido.

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
        'terms_accepted',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'terms_accepted' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Provider.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'user_id' => 'required|integer|exists:users,id',
            'terms_accepted' => 'required|boolean',
        ];
    }

    /**
     * Orçamentos criados por este provedor.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Serviços oferecidos por este provedor.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Assinaturas de planos associadas a este provedor.
     */
    public function planSubscriptions(): HasMany
    {
        return $this->hasMany(PlanSubscription::class);
    }

    /**
     * Boot method do modelo Provider.
     */
    protected static function boot(): void
    {
        parent::boot();
        static::bootTenantScoped();

        // A unicidade (tenant_id, user_id) agora é garantida pelo índice único no banco de dados
        // Isso permite criação idempotente e tratamento adequado de exceções no service/repository
    }

    /**
     * Scope para provedores ativos.
     */
    public function scopeActive($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * ProviderCredentials associadas a este provedor (se aplicável).
     */
    public function providerCredentials(): HasMany
    {
        return $this->hasMany(ProviderCredential::class);
    }

    /**
     * Ordens de pagamento MercadoPago associadas a este provedor.
     */
    public function merchantOrderMercadoPago(): HasMany
    {
        return $this->hasMany(MerchantOrderMercadoPago::class);
    }

    /**
     * Pagamentos de planos MercadoPago associadas a este provedor.
     */
    public function paymentMercadoPagoPlans(): HasMany
    {
        return $this->hasMany(PaymentMercadoPagoPlan::class);
    }

    /**
     * Get the tenant that owns the Provider.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user associated with the Provider.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the common data associated with the Provider.
     */
    public function commonData(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CommonData::class);
    }

    /**
     * Get the contact associated with the Provider.
     */
    public function contact(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Contact::class);
    }

    /**
     * Get the address associated with the Provider.
     */
    public function address(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Address::class);
    }

    /**
     * Check if provider is a company (PJ).
     */
    public function isCompany(): bool
    {
        return $this->commonData?->type === CommonData::TYPE_COMPANY;
    }

    /**
     * Check if provider is an individual (PF).
     */
    public function isIndividual(): bool
    {
        return $this->commonData?->type === CommonData::TYPE_INDIVIDUAL;
    }

    /**
     * Get the business data associated with the Provider (PJ only).
     */
    public function businessData(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BusinessData::class);
    }
}
