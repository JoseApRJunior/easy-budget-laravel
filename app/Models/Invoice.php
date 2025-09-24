<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Invoice extends Model
{
    use TenantScoped;

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
        'code',
        'invoice_statuses_id',
        'subtotal',
        'total',
        'due_date',
        'transaction_date',
        'payment_method',
        'payment_id',
        'transaction_amount',
        'public_hash',
        'discount',
        'description',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'           => 'integer',
        'service_id'          => 'integer',
        'customer_id'         => 'integer',
        'invoice_statuses_id' => 'integer',
        'code'                => 'string',
        'subtotal'            => 'decimal:2',
        'total'               => 'decimal:2',
        'due_date'            => 'date',
        'transaction_date'    => 'datetime',
        'payment_method'      => 'string',
        'payment_id'          => 'string',
        'transaction_amount'  => 'decimal:2',
        'public_hash'         => 'string',
        'discount'            => 'decimal:2',
        'description'         => 'string',
        'notes'               => 'string',
        'created_at'          => 'immutable_datetime',
        'updated_at'          => 'immutable_datetime',
    ];

    /**
     * Get the tenant that owns the Invoice.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the customer that owns the Invoice.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo( Customer::class);
    }

    /**
     * Get the invoice status that owns the Invoice.
     */
    public function invoiceStatus(): BelongsTo
    {
        return $this->belongsTo( InvoiceStatus::class, 'invoice_statuses_id' );
    }

    /**
     * Get the service that owns the Invoice.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo( Service::class);
    }

    /**
     * Accessor para tratar valores zero-date no updated_at.
     */
    public function getUpdatedAtAttribute( $value )
    {
        return ( $value === '0000-00-00 00:00:00' || empty( $value ) ) ? null : new \DateTime( $value );
    }

}