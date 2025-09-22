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
        'notes',
        'description',
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
        'due_date'            => 'datetime',
        'transaction_date'    => 'datetime',
        'payment_method'      => 'string',
        'payment_id'          => 'string',
        'transaction_amount'  => 'decimal:2',
        'public_hash'         => 'string',
        'discount'            => 'decimal:2',
        'notes'               => 'string',
        'description'         => 'string',
        'created_at'          => 'datetime_immutable',
        'updated_at'          => 'datetime_immutable',
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

}
