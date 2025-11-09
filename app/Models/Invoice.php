<?php
declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Traits\TenantScoped;
use App\Models\UserConfirmationToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

}
