<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Address;
use App\Models\Budget;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, TenantScoped;

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_DELETED  = 'deleted';

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
        'common_data_id',
        'contact_id',
        'address_id',
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
        'tenant_id'      => 'integer',
        'common_data_id' => 'integer',
        'contact_id'     => 'integer',
        'address_id'     => 'integer',
        'status'         => 'string', // enum('active', 'inactive', 'deleted') crie const
        'created_at'     => 'immutable_datetime',
        'updated_at'     => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Customer.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'      => 'required|integer|exists:tenants,id',
            'common_data_id' => 'nullable|integer|exists:common_datas,id',
            'contact_id'     => 'nullable|integer|exists:contacts,id',
            'address_id'     => 'nullable|integer|exists:addresses,id',
            'status'         => 'required|string|in:' . implode( ',', self::STATUSES ),
        ];
    }

    /**
     * Get the tenant that owns the Customer.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the common data associated with the Customer.
     */
    public function commonData(): BelongsTo
    {
        return $this->belongsTo( CommonData::class);
    }

    /**
     * Get the contact associated with the Customer.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo( Contact::class);
    }

    /**
     * Get the address associated with the Customer.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo( Address::class);
    }

    /**
     * Get the budgets for the Customer.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany( Budget::class);
    }

    /**
     * Get the invoices for the Customer.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany( Invoice::class);
    }

    /**
     * Get the addresses for the Customer.
     */
    public function addresses()
    {
        return $this->hasMany( CustomerAddress::class);
    }

    /**
     * Get the primary address for the Customer.
     */
    public function primaryAddress()
    {
        return $this->hasOne( CustomerAddress::class)->where( 'is_primary', true );
    }

    /**
     * Get the contacts for the Customer.
     */
    public function contacts()
    {
        return $this->hasMany( CustomerContact::class);
    }

    /**
     * Get the primary contacts for the Customer.
     */
    public function primaryContacts()
    {
        return $this->hasMany( CustomerContact::class)->where( 'is_primary', true );
    }

    /**
     * Get the interactions for the Customer.
     */
    public function interactions()
    {
        return $this->hasMany( CustomerInteraction::class);
    }

    /**
     * Get the tags for the Customer.
     */
    public function tags()
    {
        return $this->belongsToMany( CustomerTag::class, 'customer_tag_assignments' );
    }

    /**
     * Get the customer's primary email.
     */
    public function getPrimaryEmailAttribute(): ?string
    {
        $primaryContact = $this->contacts()->where( 'type', 'email' )->where( 'is_primary', true )->first();
        return $primaryContact?->value;
    }

    /**
     * Get the customer's primary phone.
     */
    public function getPrimaryPhoneAttribute(): ?string
    {
        $primaryContact = $this->contacts()->where( 'type', 'phone' )->where( 'is_primary', true )->first();
        return $primaryContact?->value;
    }

    /**
     * Get the customer's last interaction.
     */
    public function getLastInteractionAttribute(): ?CustomerInteraction
    {
        return $this->interactions()->latest( 'interaction_date' )->first();
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
            ->whereNotNull( 'next_action_date' )
            ->where( 'next_action_date', '<', now() )
            ->where( function ( $query ) {
                $query->whereNull( 'outcome' )
                    ->orWhere( 'outcome', '!=', 'completed' );
            } )
            ->exists();
    }

    /**
     * Get pending follow-ups for the customer.
     */
    public function getPendingFollowUpsAttribute()
    {
        return $this->interactions()
            ->whereNotNull( 'next_action' )
            ->whereNotNull( 'next_action_date' )
            ->where( 'next_action_date', '>=', now() )
            ->where( function ( $query ) {
                $query->whereNull( 'outcome' )
                    ->orWhere( 'outcome', '!=', 'completed' );
            } )
            ->get();
    }

    /**
     * Add a tag to the customer.
     */
    public function addTag( CustomerTag $tag ): void
    {
        if ( !$this->tags()->where( 'customer_tag_id', $tag->id )->exists() ) {
            $this->tags()->attach( $tag->id );
        }
    }

    /**
     * Remove a tag from the customer.
     */
    public function removeTag( CustomerTag $tag ): void
    {
        $this->tags()->detach( $tag->id );
    }

    /**
     * Sync tags for the customer.
     */
    public function syncTags( array $tagIds ): void
    {
        $this->tags()->sync( $tagIds );
    }

    /**
     * Get the customer's status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ( $this->status ) {
            self::STATUS_ACTIVE   => 'Ativo',
            self::STATUS_INACTIVE => 'Inativo',
            self::STATUS_DELETED  => 'Excluído',
            default               => ucfirst( $this->status ),
        };
    }

    /**
     * Get the customer's priority level label.
     */
    public function getPriorityLevelLabelAttribute(): string
    {
        return match ( $this->priority_level ?? 'normal' ) {
            'normal'  => 'Normal',
            'vip'     => 'VIP',
            'premium' => 'Premium',
            default   => ucfirst( $this->priority_level ),
        };
    }

    /**
     * Get the customer's type label.
     */
    public function getCustomerTypeLabelAttribute(): string
    {
        return match ( $this->customer_type ?? 'individual' ) {
            'individual' => 'Pessoa Física',
            'company'    => 'Pessoa Jurídica',
            default      => ucfirst( $this->customer_type ),
        };
    }

    /**
     * Scope para buscar clientes ativos.
     */
    public function scopeActive( $query )
    {
        return $query->where( 'status', self::STATUS_ACTIVE );
    }

    /**
     * Scope para ordenar clientes por nome através do relacionamento.
     */
    public function scopeOrdered( $query )
    {
        // Abordagem simplificada: ordenar por ID para evitar problemas de join
        // Em produção, pode ser melhorado com uma coluna de nome normalizada na tabela customers
        return $query->orderBy( 'customers.id' );
    }

    /**
     * Scope para buscar clientes VIP.
     */
    public function scopeVip( $query )
    {
        return $query->where( 'priority_level', 'vip' );
    }

    /**
     * Scope para buscar clientes por tipo.
     */
    public function scopeOfType( $query, string $type )
    {
        return $query->where( 'customer_type', $type );
    }

    /**
     * Scope para buscar clientes com interações recentes.
     */
    public function scopeWithRecentInteractions( $query, int $days = 30 )
    {
        return $query->whereHas( 'interactions', function ( $q ) use ( $days ) {
            $q->where( 'interaction_date', '>=', now()->subDays( $days ) );
        } );
    }

    /**
     * Scope para buscar clientes com ações pendentes.
     */
    public function scopeWithPendingActions( $query )
    {
        return $query->whereHas( 'interactions', function ( $q ) {
            $q->whereNotNull( 'next_action' )
                ->whereNotNull( 'next_action_date' )
                ->where( 'next_action_date', '>=', now() )
                ->where( function ( $subQuery ) {
                    $subQuery->whereNull( 'outcome' )
                        ->orWhere( 'outcome', '!=', 'completed' );
                } );
        } );
    }

    /**
     * Scope para buscar clientes por tag.
     */
    public function scopeWithTag( $query, CustomerTag $tag )
    {
        return $query->whereHas( 'tags', function ( $q ) use ( $tag ) {
            $q->where( 'customer_tags.id', $tag->id );
        } );
    }

    /**
     * Scope para buscar clientes por múltiplas tags.
     */
    public function scopeWithTags( $query, array $tagIds )
    {
        return $query->whereHas( 'tags', function ( $q ) use ( $tagIds ) {
            $q->whereIn( 'customer_tags.id', $tagIds );
        } );
    }

    /**
     * Accessor para tratar valores zero-date no updated_at.
     */
    public function getUpdatedAtAttribute( $value )
    {
        return ( $value === '0000-00-00 00:00:00' || empty( $value ) ) ? null : \DateTime::createFromFormat( 'Y-m-d H:i:s', $value );
    }

    /**
     * Get the customer's full name from common data.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        $firstName = $this->commonData?->first_name ?? '';
        $lastName  = $this->commonData?->last_name ?? '';

        return trim( $firstName . ' ' . $lastName );
    }

    /**
     * Get the customer's formatted CPF.
     *
     * @return string
     */
    public function getFormattedCpfAttribute(): string
    {
        if ( $this->commonData?->cpf ) {
            return $this->formatCpf( $this->commonData->cpf );
        }

        return '';
    }

    /**
     * Get the customer's formatted CNPJ.
     *
     * @return string
     */
    public function getFormattedCnpjAttribute(): string
    {
        if ( $this->commonData?->cnpj ) {
            return $this->formatCnpj( $this->commonData->cnpj );
        }

        return '';
    }

    /**
     * Get the customer's formatted primary phone.
     *
     * @return string
     */
    public function getFormattedPhoneAttribute(): string
    {
        return $this->formatPhone( $this->contact?->phone ?? '' );
    }

    /**
     * Get the customer's formatted business phone.
     *
     * @return string
     */
    public function getFormattedBusinessPhoneAttribute(): string
    {
        return $this->formatPhone( $this->contact?->phone_business ?? '' );
    }

    /**
     * Check if the customer is a company (CNPJ).
     *
     * @return bool
     */
    public function isCompany(): bool
    {
        return !empty( $this->commonData?->cnpj );
    }

    /**
     * Get the customer's age based on birth date.
     *
     * @return int|null
     */
    public function getAgeAttribute(): ?int
    {
        if ( !$this->commonData?->birth_date ) {
            return null;
        }

        return $this->commonData->birth_date->age;
    }

    /**
     * Get the customer's email from contact.
     *
     * @return string|null
     */
    public function getEmailAttribute(): ?string
    {
        return $this->contact?->email;
    }

    /**
     * Get the customer's business email from contact.
     *
     * @return string|null
     */
    public function getEmailBusinessAttribute(): ?string
    {
        return $this->contact?->email_business;
    }

    /**
     * Get the customer's primary phone from contact.
     *
     * @return string|null
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->contact?->phone;
    }

    /**
     * Get the customer's business phone from contact.
     *
     * @return string|null
     */
    public function getPhoneBusinessAttribute(): ?string
    {
        return $this->contact?->phone_business;
    }

    /**
     * Get the customer's website from contact.
     *
     * @return string|null
     */
    public function getContactWebsiteAttribute(): ?string
    {
        return $this->contact?->website;
    }

    /**
     * Get the customer's first name from common data.
     *
     * @return string|null
     */
    public function getFirstNameAttribute(): ?string
    {
        return $this->commonData?->first_name;
    }

    /**
     * Get the customer's last name from common data.
     *
     * @return string|null
     */
    public function getLastNameAttribute(): ?string
    {
        return $this->commonData?->last_name;
    }

    /**
     * Get the customer's CPF from common data.
     *
     * @return string|null
     */
    public function getCpfAttribute(): ?string
    {
        return $this->commonData?->cpf;
    }

    /**
     * Get the customer's CNPJ from common data.
     *
     * @return string|null
     */
    public function getCnpjAttribute(): ?string
    {
        return $this->commonData?->cnpj;
    }

    /**
     * Get the customer's company name from common data.
     *
     * @return string|null
     */
    public function getCompanyNameAttribute(): ?string
    {
        return $this->commonData?->company_name;
    }

    /**
     * Get the customer's profession from common data.
     *
     * @return string|null
     */
    public function getProfessionAttribute(): ?string
    {
        return $this->commonData?->profession?->name;
    }

    /**
     * Get the customer's area of activity from common data.
     *
     * @return string|null
     */
    public function getAreaOfActivityAttribute(): ?string
    {
        return $this->commonData?->areaOfActivity?->name;
    }

    /**
     * Get the customer's full address.
     *
     * @return string
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address;

        if ( !$address ) {
            return '';
        }

        $parts = array_filter( [
            $address->address,
            $address->address_number,
            $address->neighborhood,
            $address->city,
            $address->state,
            $address->cep,
        ] );

        return implode( ', ', $parts );
    }

}
