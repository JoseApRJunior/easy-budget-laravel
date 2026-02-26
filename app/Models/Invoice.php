<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $service_id
 * @property int $customer_id
 * @property InvoiceStatus $status
 * @property int|null $user_confirmation_token_id
 * @property string $code
 * @property float $subtotal
 * @property float $discount
 * @property float $total
 * @property \Illuminate\Support\Carbon|null $issue_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property string|null $payment_method
 * @property string|null $payment_id
 * @property float|null $transaction_amount
 * @property \Illuminate\Support\Carbon|null $transaction_date
 * @property string|null $notes
 * @property bool $is_automatic
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Invoice extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

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
    protected $table = 'invoices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'service_id',
        'customer_id',
        'status',
        'user_confirmation_token_id',
        'code',
        'subtotal',
        'discount',
        'total',
        'issue_date',
        'due_date',
        'payment_method',
        'payment_id',
        'transaction_amount',
        'transaction_date',
        'notes',
        'is_automatic',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'service_id' => 'integer',
        'customer_id' => 'integer',
        'status' => InvoiceStatus::class,
        'user_confirmation_token_id' => 'integer',
        'code' => 'string',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'transaction_date' => 'datetime',
        'payment_method' => 'string',
        'payment_id' => 'string',
        'transaction_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'notes' => 'string',
        'is_automatic' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Invoice.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'service_id' => 'required|integer|exists:services,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'status' => 'required|string|in:' . implode(',', array_column(InvoiceStatus::cases(), 'value')),
            'user_confirmation_token_id' => 'nullable|integer|exists:user_confirmation_tokens,id',
            'code' => 'required|string|max:50|unique:invoices,code',
            'subtotal' => 'required|numeric|min:0|max:999999.99',
            'discount' => 'required|numeric|min:0|max:999999.99',
            'total' => 'required|numeric|min:0|max:999999.99',
            'issue_date' => 'nullable|date',
            'due_date' => 'nullable|date|after:today',
            'payment_method' => 'nullable|string|max:50',
            'payment_id' => 'nullable|string|max:255',
            'transaction_amount' => 'nullable|numeric|min:0|max:999999.99',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string|max:65535',
            'is_automatic' => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns the Invoice.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the customer that owns the Invoice.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the invoice status enum.
     */
    public function getInvoiceStatusAttribute(): ?InvoiceStatus
    {
        return $this->status;
    }

    /**
     * Get the service that owns the Invoice.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the invoice items for the Invoice.
     */
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the user confirmation token for the Invoice.
     */
    public function userConfirmationToken()
    {
        return $this->belongsTo(UserConfirmationToken::class);
    }

    /**
     * Get the payments for the Invoice.
     */
    public function paymentMercadoPagoInvoice()
    {
        return $this->hasMany(PaymentMercadoPagoInvoice::class, 'invoice_id');
    }

    /**
     * Get the shares for the invoice.
     */
    public function shares()
    {
        return $this->hasMany(InvoiceShare::class);
    }

    /**
     * Get the public URL for the invoice (using the latest active share).
     */
    public function getPublicUrl(): ?string
    {
        $share = $this->shares()
            ->where('is_active', true)
            ->where('status', \App\Enums\InvoiceShareStatus::ACTIVE)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if ($share) {
            return route('services.public.invoices.public.show', ['hash' => $share->share_token]);
        }

        return null;
    }

    /**
     * Accessor para tratar valores zero-date no updated_at.
     */
    public function getUpdatedAtAttribute($value)
    {
        return ($value === '0000-00-00 00:00:00' || empty($value)) ? null : new \DateTime($value);
    }

    /**
     * Get the name of the invoice status for backward compatibility with views.
     */
    public function getNameAttribute(): ?string
    {
        return $this->status?->getDescription();
    }

    /**
     * Get the name of the invoice status.
     */
    public function getStatusNameAttribute(): ?string
    {
        return $this->status?->label();
    }

    /**
     * Get the color of the invoice status for backward compatibility with views.
     */
    public function getColorAttribute(): string
    {
        return $this->status?->getColor() ?? '#6c757d';
    }

    /**
     * Get the color of the invoice status.
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status?->getColor() ?? '#6c757d';
    }

    /**
     * Get the icon of the invoice status.
     */
    public function getStatusIconAttribute(): string
    {
        return $this->status?->icon() ?? 'bi-info-circle';
    }

    /**
     * Get the slug of the invoice status for backward compatibility with views.
     */
    public function getSlugAttribute(): string
    {
        return $this->status?->value ?? '';
    }

    /**
     * Get the slug of the invoice status in uppercase for comparison in views.
     */
    public function getStatusSlugAttribute(): string
    {
        return strtoupper($this->status?->value ?? '');
    }

    /**
     * Get the customer name.
     */
    public function getCustomerNameAttribute(): ?string
    {
        return $this->customer?->name;
    }

    /**
     * Get the customer email.
     */
    public function getCustomerEmailAttribute(): ?string
    {
        return $this->customer?->email;
    }

    /**
     * Get the customer business email.
     */
    public function getCustomerEmailBusinessAttribute(): ?string
    {
        return $this->customer?->commonData?->email_business ?? $this->customer?->email;
    }

    /**
     * Get the customer phone.
     */
    public function getCustomerPhoneAttribute(): ?string
    {
        return $this->customer?->contact?->phone;
    }

    /**
     * Get the customer business phone.
     */
    public function getCustomerPhoneBusinessAttribute(): ?string
    {
        return $this->customer?->contact?->phone_business ?? $this->customer?->contact?->phone;
    }

    /**
     * Get the provider company name.
     */
    public function getProviderCompanyNameAttribute(): ?string
    {
        return $this->tenant?->provider?->commonData?->company_name 
            ?? $this->tenant?->provider?->commonData?->full_name 
            ?? $this->tenant?->name;
    }

    /**
     * Get the provider name.
     */
    public function getProviderNameAttribute(): ?string
    {
        return $this->tenant?->provider?->commonData?->full_name ?? $this->tenant?->name;
    }

    /**
     * Get the provider email.
     */
    public function getProviderEmailAttribute(): ?string
    {
        return $this->tenant?->provider?->contact?->email_business 
            ?? $this->tenant?->provider?->contact?->email_personal 
            ?? $this->tenant?->email;
    }

    /**
     * Get the provider phone.
     */
    public function getProviderPhoneAttribute(): ?string
    {
        return $this->tenant?->provider?->contact?->phone_business 
            ?? $this->tenant?->provider?->contact?->phone_personal 
            ?? $this->tenant?->phone;
    }

    /**
     * Get the tenant company name.
     */
    public function getTenantCompanyNameAttribute(): ?string
    {
        return $this->tenant?->provider?->commonData?->company_name 
            ?? $this->tenant?->provider?->commonData?->full_name 
            ?? $this->tenant?->name;
    }

    /**
     * Get the service code.
     */
    public function getServiceCodeAttribute(): ?string
    {
        return $this->service?->code;
    }

    /**
     * Get the service description.
     */
    public function getServiceDescriptionAttribute(): ?string
    {
        return $this->service?->description;
    }

    /**
     * Get the description of the invoice status for backward compatibility with views.
     */
    public function getStatusDescriptionAttribute(): ?string
    {
        return $this->status?->getDescription();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
