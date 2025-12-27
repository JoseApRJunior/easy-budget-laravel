<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommonData extends Model
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
    protected $table = 'common_datas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    const TYPE_INDIVIDUAL = 'individual';

    const TYPE_COMPANY = 'company';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'provider_id',
        'type',
        'first_name',
        'last_name',
        'birth_date',
        'cpf',
        'company_name',
        'cnpj',
        'description',
        'area_of_activity_id',
        'profession_id',
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
        'type' => 'string',
        'first_name' => 'string',
        'last_name' => 'string',
        'birth_date' => 'date',
        'cpf' => 'string',
        'company_name' => 'string',
        'cnpj' => 'string',
        'description' => 'string',
        'area_of_activity_id' => 'integer',
        'profession_id' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo CommonData.
     */
    public static function businessRules(string $type): array
    {
        $rules = [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'type' => 'required|in:individual,company',
            'description' => 'nullable|string|max:65535',
            'area_of_activity_id' => 'nullable|integer|exists:areas_of_activity,id',
            'profession_id' => 'nullable|integer|exists:professions,id',
        ];

        if ($type === self::TYPE_INDIVIDUAL) {
            $rules['first_name'] = 'required|string|max:100';
            $rules['last_name'] = 'required|string|max:100';
            $rules['cpf'] = 'required|string|size:11|unique:common_datas,cpf';
            $rules['birth_date'] = 'nullable|date|before:today';
        } else {
            $rules['company_name'] = 'required|string|max:255';
            $rules['cnpj'] = 'required|string|size:14|unique:common_datas,cnpj';
        }

        return $rules;
    }

    /**
     * Get the tenant that owns the CommonData.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the area of activity that owns the CommonData.
     */
    public function areaOfActivity(): BelongsTo
    {
        return $this->belongsTo(AreaOfActivity::class, 'area_of_activity_id');
    }

    /**
     * Get the profession that owns the CommonData.
     */
    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class, 'profession_id');
    }

    /**
     * Get the customer associated with the CommonData.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the provider associated with the CommonData.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Scope to filter by individual type.
     */
    public function scopeIndividual($query)
    {
        return $query->where('type', self::TYPE_INDIVIDUAL);
    }

    /**
     * Scope to filter by company type.
     */
    public function scopeCompany($query)
    {
        return $query->where('type', self::TYPE_COMPANY);
    }

    /**
     * Check if is individual (PF).
     */
    public function isIndividual(): bool
    {
        return $this->type === self::TYPE_INDIVIDUAL;
    }

    /**
     * Check if is company (PJ).
     */
    public function isCompany(): bool
    {
        return $this->type === self::TYPE_COMPANY;
    }
}
