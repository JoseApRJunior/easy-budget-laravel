<?php
declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Traits\TenantScoped;
use App\Models\UserConfirmationToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $service_id
 * @property int $customer_id
 * @property InvoiceStatus $status
 * @property int|null $user_confirmation_token_id
 * @property string $code
 * @property string|null $public_hash
 * @property float $subtotal
 * @property float $discount
 * @property float $total
 * @property \Illuminate\Support\Carbon $due_date
 * @property string|null $payment_method
 * @property string|null $payment_id
 * @property float|null $transaction_amount
 * @property \Illuminate\Support\Carbon|null $transaction_date
 * @property string|null $public_token
 * @property \Illuminate\Support\Carbon|null $public_expires_at
 * @property string|null $notes
 * @property bool $is_automatic
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Invoice extends Model
{
    use TenantScoped, SoftDeletes, HasFactory;

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
        'public_hash',
        'subtotal',
        'discount',
        'total',
        'due_date',
        'payment_method',
        'payment_id',
        'transaction_amount',
        'transaction_date',
        'public_token',
        'public_expires_at',
        'notes',
        'is_automatic',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'                  => 'integer',
        'service_id'                 => 'integer',
        'customer_id'                => 'integer',
        'status'                     => InvoiceStatus::class,
        'user_confirmation_token_id' => 'integer',
        'code'                       => 'string',
        'subtotal'                   => 'decimal:2',
        'total'                      => 'decimal:2',
        'due_date'                   => 'date',
        'transaction_date'           => 'datetime',
        'payment_method'             => 'string',
        'payment_id'                 => 'string',
        'transaction_amount'         => 'decimal:2',
        'public_hash'                => 'string',
        'public_token'               => 'string',
        'public_expires_at'          => 'datetime',
        'discount'                   => 'decimal:2',
        'notes'                      => 'string',
        'is_automatic'               => 'boolean',
        'created_at'                 => 'immutable_datetime',
        'updated_at'                 => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Invoice.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'                  => 'required|integer|exists:tenants,id',
            'service_id'                 => 'required|integer|exists:services,id',
            'customer_id'                => 'required|integer|exists:customers,id',
            'status'                     => 'required|string|in:' . implode( ',', array_column( InvoiceStatus::cases(), 'value' ) ),
            'user_confirmation_token_id' => 'nullable|integer|exists:user_confirmation_tokens,id',
            'code'                       => 'required|string|max:50|unique:invoices,code',
            'subtotal'                   => 'required|numeric|min:0|max:999999.99',
            'discount'                   => 'required|numeric|min:0|max:999999.99',
            'total'                      => 'required|numeric|min:0|max:999999.99',
            'due_date'                   => 'nullable|date|after:today',
            'payment_method'             => 'nullable|string|max:50',
            'payment_id'                 => 'nullable|string|max:255',
            'transaction_amount'         => 'nullable|numeric|min:0|max:999999.99',
            'transaction_date'           => 'nullable|date',
            'public_token'               => 'nullable|string|size:43', // base64url format: 32 bytes = 43 caracteres
            'public_expires_at'          => 'nullable|date',
            'notes'                      => 'nullable|string|max:65535',
            'is_automatic'               => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns the Invoice.
     */
    public function tenant()
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the customer that owns the Invoice.
     */
    public function customer()
    {
        return $this->belongsTo( Customer::class);
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
        return $this->belongsTo( Service::class);
    }

    /**
     * Get the invoice items for the Invoice.
     */
    public function invoiceItems()
    {
        return $this->hasMany( InvoiceItem::class);
    }

    /**
     * Get the user confirmation token for the Invoice.
     */
    public function userConfirmationToken()
    {
        return $this->belongsTo( UserConfirmationToken::class);
    }

    /**
     * Accessor para tratar valores zero-date no updated_at.
     */
    public function getUpdatedAtAttribute( $value )
    {
        return ( $value === '0000-00-00 00:00:00' || empty( $value ) ) ? null : new \DateTime( $value );
    }

    /**
     * Get the name of the invoice status for backward compatibility with views.
     */
    public function getNameAttribute(): ?string
    {
        return $this->status?->getDescription();
    }

    /**
     * Get the color of the invoice status for backward compatibility with views.
     */
    public function getColorAttribute(): string
    {
        return $this->status?->getColor() ?? '#6c757d';
    }

    /**
     * Get the slug of the invoice status for backward compatibility with views.
     */
    public function getSlugAttribute(): string
    {
        return $this->status?->value ?? '';
    }

    /**
     * Get the description of the invoice status for backward compatibility with views.
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->status?->getDescription();
    }

}
