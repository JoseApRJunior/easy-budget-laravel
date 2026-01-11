<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Customer extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_DELETED = 'deleted';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_DELETED,
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Resolve route binding using tenant scope.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();

        return $this->where($field, $value)
            ->where('tenant_id', Auth::user()?->tenant_id)
            ->first();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'status' => 'string',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Customer.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'status' => 'required|string|in:'.implode(',', self::STATUSES),
        ];
    }

    /**
     * Get the tenant that owns the Customer.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the common data associated with the Customer.
     */
    public function commonData(): HasOne
    {
        return $this->hasOne(CommonData::class);
    }

    /**
     * Get the contacts associated with the Customer.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the contact associated with the Customer.
     */
    public function contact(): HasOne
    {
        return $this->hasOne(Contact::class)->latest(); // Assumindo que o singular retorna o mais recente ou principal
    }

    /**
     * Get the address associated with the Customer.
     */
    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    /**
     * Get the business data associated with the Customer (PJ only).
     */
    public function businessData(): HasOne
    {
        return $this->hasOne(BusinessData::class);
    }

    /**
     * Get the budgets for the Customer.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Get the services for the Customer (through budgets).
     */
    public function services(): HasManyThrough
    {
        return $this->hasManyThrough(Service::class, Budget::class);
    }

    /**
     * Get the invoices for the Customer.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if customer is a company (PJ).
     */
    public function isCompany(): bool
    {
        return $this->commonData?->type === CommonData::TYPE_COMPANY;
    }

    /**
     * Check if customer is an individual (PF).
     */
    public function isIndividual(): bool
    {
        return $this->commonData?->type === CommonData::TYPE_INDIVIDUAL;
    }

    /**
     * Get the interactions for the Customer.
     */
    public function interactions()
    {
        return $this->hasMany(CustomerInteraction::class);
    }

    /**
     * Get the tags for the Customer.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CustomerTag::class, 'customer_tag_assignments');
    }

    /**
     * Get the customer's full name or company name.
     */
    public function getNameAttribute(): ?string
    {
        if ($this->isCompany()) {
            return $this->commonData?->company_name;
        }

        if ($this->commonData?->first_name || $this->commonData?->last_name) {
            return trim(($this->commonData->first_name ?? '') . ' ' . ($this->commonData->last_name ?? ''));
        }

        return null;
    }

    /**
     * Get the customer's company name.
     */
    public function getCompanyNameAttribute(): ?string
    {
        return $this->commonData?->company_name;
    }

    /**
     * Get the customer's primary email.
     */
    public function getPrimaryEmailAttribute(): ?string
    {
        return $this->contact?->email_personal ?? $this->contact?->email_business;
    }

    /**
     * Get the customer's email (alias for primary_email).
     */
    public function getEmailAttribute(): ?string
    {
        return $this->primary_email;
    }

    /**
     * Get the customer's primary phone.
     */
    public function getPrimaryPhoneAttribute(): ?string
    {
        return $this->contact?->phone_personal ?? $this->contact?->phone_business;
    }

    /**
     * Get the customer's phone (alias for primary_phone).
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->primary_phone;
    }

    /**
     * Get the customer's last interaction.
     */
    public function getLastInteractionAttribute(): ?CustomerInteraction
    {
        return $this->interactions()->latest('interaction_date')->first();
    }

    /**
     * Get the customer's interaction count.
     */
    public function getInteractionCountAttribute(): int
    {
        return $this->interactions()->count();
    }

    /**
     * Get the customer's tag count.
     */
    public function getTagCountAttribute(): int
    {
        return $this->tags()->count();
    }

    /**
     * Check if customer has overdue interactions.
     */
    public function hasOverdueInteractions(): bool
    {
        return $this->interactions()
            ->whereNotNull('next_action_date')
            ->where('next_action_date', '<', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('outcome')
                    ->orWhere('outcome', '!=', 'completed');
            })
            ->exists();
    }

    /**
     * Get pending follow-ups for the customer.
     */
    public function getPendingFollowUpsAttribute()
    {
        return $this->interactions()
            ->whereNotNull('next_action')
            ->whereNotNull('next_action_date')
            ->where('next_action_date', '>=', \Illuminate\Support\Carbon::now())
            ->where(function ($query) {
                $query->whereNull('outcome')
                    ->orWhere('outcome', '!=', 'completed');
            })
            ->get();
    }

    /**
     * Add a tag to the customer.
     */
    public function addTag(CustomerTag $tag): void
    {
        if (! $this->tags()->where('customer_tag_id', $tag->id)->exists()) {
            $this->tags()->attach($tag->id);
        }
    }

    /**
     * Remove a tag from the customer.
     */
    public function removeTag(CustomerTag $tag): void
    {
        $this->tags()->detach($tag->id);
    }

    /**
     * Sync tags for the customer.
     */
    public function syncTags(array $tagIds): void
    {
        $this->tags()->sync($tagIds);
    }

    /**
     * Get the customer's status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Ativo',
            self::STATUS_INACTIVE => 'Inativo',
            self::STATUS_DELETED => 'Excluído',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the customer's priority level label.
     */
    public function getPriorityLevelLabelAttribute(): string
    {
        return match ($this->priority_level ?? 'normal') {
            'normal' => 'Normal',
            'vip' => 'VIP',
            'premium' => 'Premium',
            default => ucfirst($this->priority_level),
        };
    }

    /**
     * Get the customer's type label.
     */
    public function getCustomerTypeLabelAttribute(): string
    {
        return match ($this->customer_type ?? 'individual') {
            'individual' => 'Pessoa Física',
            'company' => 'Pessoa Jurídica',
            default => ucfirst($this->customer_type),
        };
    }

    /**
     * Scope para buscar clientes ativos.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para ordenar clientes por nome através do relacionamento.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('customers.id');
    }

    /**
     * Scope para buscar clientes VIP.
     */
    public function scopeVip($query)
    {
        return $query->where('priority_level', 'vip');
    }

    /**
     * Scope para buscar clientes por tipo.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('customer_type', $type);
    }

    /**
     * Scope para buscar clientes com interações recentes.
     */
    public function scopeWithRecentInteractions($query, int $days = 30)
    {
        return $query->whereHas('interactions', function ($q) use ($days) {
            $q->where('interaction_date', '>=', \Illuminate\Support\Carbon::now()->subDays($days));
        });
    }

    /**
     * Scope para buscar clientes com ações pendentes.
     */
    public function scopeWithPendingActions($query)
    {
        return $query->whereHas('interactions', function ($q) {
            $q->whereNotNull('next_action')
                ->whereNotNull('next_action_date')
                ->where('next_action_date', '>=', \Illuminate\Support\Carbon::now())
                ->where(function ($subQuery) {
                    $subQuery->whereNull('outcome')
                        ->orWhere('outcome', '!=', 'completed');
                });
        });
    }

    /**
     * Scope para buscar clientes por tag.
     */
    public function scopeWithTag($query, CustomerTag $tag)
    {
        return $query->whereHas('tags', function ($q) use ($tag) {
            $q->where('customer_tags.id', $tag->id);
        });
    }

    /**
     * Scope para buscar clientes por múltiplas tags.
     */
    public function scopeWithTags($query, array $tagIds)
    {
        return $query->whereHas('tags', function ($q) use ($tagIds) {
            $q->whereIn('customer_tags.id', $tagIds);
        });
    }

    /**
     * Accessor para tratar valores zero-date no updated_at.
     */
    public function getUpdatedAtAttribute($value)
    {
        return ($value === '0000-00-00 00:00:00' || empty($value)) ? null : DateTime::createFromFormat('Y-m-d H:i:s', $value);
    }

    /**
     * Get the customer's formatted CPF.
     */
    public function getFormattedCpfAttribute(): string
    {
        if ($this->commonData?->cpf) {
            return $this->formatCpf($this->commonData->cpf);
        }

        return '';
    }

    /**
     * Get the customer's formatted CNPJ.
     */
    public function getFormattedCnpjAttribute(): string
    {
        if ($this->commonData?->cnpj) {
            return $this->formatCnpj($this->commonData->cnpj);
        }

        return '';
    }

    /**
     * Get the customer's age based on birth date.
     */
    public function getAgeAttribute(): ?int
    {
        if (! $this->commonData?->birth_date) {
            return null;
        }

        return $this->commonData->birth_date->age;
    }

    /**
     * Get the customer's full address.
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address;

        if (! $address) {
            return '';
        }

        $parts = array_filter([
            $address->address,
            $address->address_number,
            $address->neighborhood,
            $address->city,
            $address->state,
            $address->cep,
        ]);

        return implode(', ', $parts);
    }

    // /**
    //  * Formatar CPF (XXX.XXX.XXX-XX)
    //  */
    // public function formatCpf(string $cpf): string
    // {
    //     // Remove tudo que não é número
    //     $cpf = preg_replace('/[^0-9]/', '', $cpf);

    //     // Aplica a máscara
    //     return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    // }

    // /**
    //  * Formatar CNPJ (XX.XXX.XXX/XXXX-XX)
    //  */
    // public function formatCnpj(string $cnpj): string
    // {
    //     // Remove tudo que não é número
    //     $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

    //     // Aplica a máscara
    //     return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    // }

    // /**
    //  * Formatar telefone ((XX) XXXXX-XXXX)
    //  */
    // public function formatPhone(string $phone): string
    // {
    //     // Remove tudo que não é número
    //     $phone = preg_replace('/[^0-9]/', '', $phone);

    //     // Aplica a máscara baseada no tamanho
    //     if (strlen($phone) === 11) {
    //         return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    //     } elseif (strlen($phone) === 10) {
    //         return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
    //     }

    //     return $phone; // Retorna original se não conseguir formatar
    // }

    // /**
    //  * Validar CPF usando algoritmo oficial
    //  */
    // public function isValidCpf(string $cpf): bool
    // {
    //     // Remove caracteres não numéricos
    //     $cpf = preg_replace('/[^0-9]/', '', $cpf);

    //     // Verifica se tem 11 dígitos
    //     if (strlen($cpf) != 11) {
    //         return false;
    //     }

    //     // Verifica se todos os dígitos são iguais
    //     if (preg_match('/^(\d)\1{10}$/', $cpf)) {
    //         return false;
    //     }

    //     // Calcula primeiro dígito verificador
    //     $sum = 0;
    //     for ($i = 0; $i < 9; $i++) {
    //         $sum += $cpf[$i] * (10 - $i);
    //     }
    //     $remainder = $sum % 11;
    //     $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

    //     // Calcula segundo dígito verificador
    //     $sum = 0;
    //     for ($i = 0; $i < 10; $i++) {
    //         $sum += $cpf[$i] * (11 - $i);
    //     }
    //     $remainder = $sum % 11;
    //     $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

    //     return $cpf[9] == $digit1 && $cpf[10] == $digit2;
    // }

    // /**
    //  * Validar CNPJ usando algoritmo oficial
    //  */
    // public function isValidCnpj(string $cnpj): bool
    // {
    //     // Remove caracteres não numéricos
    //     $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

    //     // Verifica se tem 14 dígitos
    //     if (strlen($cnpj) != 14) {
    //         return false;
    //     }

    //     // Verifica se todos os dígitos são iguais
    //     if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
    //         return false;
    //     }

    //     // Calcula primeiro dígito verificador
    //     $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    //     $sum = 0;
    //     for ($i = 0; $i < 12; $i++) {
    //         $sum += $cnpj[$i] * $weights1[$i];
    //     }
    //     $remainder = $sum % 11;
    //     $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

    //     // Calcula segundo dígito verificador
    //     $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    //     $sum = 0;
    //     for ($i = 0; $i < 13; $i++) {
    //         $sum += $cnpj[$i] * $weights2[$i];
    //     }
    //     $remainder = $sum % 11;
    //     $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

    //     return $cnpj[12] == $digit1 && $cnpj[13] == $digit2;
    // }
}
